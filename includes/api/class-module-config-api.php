<?php
/**
 * API REST para configuración dinámica de módulos móviles
 *
 * @package Flavor_Chat_IA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase que gestiona el endpoint de configuración de módulos
 */
class Flavor_Module_Config_API {

    /**
     * Namespace de la API
     *
     * @var string
     */
    private $namespace = 'flavor/v1';

    /**
     * Configuraciones de módulos
     *
     * @var array
     */
    private $modules_config = [];

    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
        $this->init_modules_config();
    }

    /**
     * Registra las rutas de la API
     */
    public function register_routes() {
        register_rest_route($this->namespace, '/modules/config', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_all_configs'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'all' => array(
                    'type'        => 'boolean',
                    'default'     => false,
                    'description' => 'Incluir todos los módulos (activos e inactivos)',
                ),
            ),
        ));

        register_rest_route($this->namespace, '/modules/config/(?P<module_id>[a-z0-9_-]+)', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_module_config'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'module_id' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));

        // Endpoint de documentación de módulos
        register_rest_route($this->namespace, '/modules/docs/(?P<module_id>[a-z0-9_-]+)', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_module_docs'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'module_id' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));

        // Endpoint para listar toda la documentación
        register_rest_route($this->namespace, '/modules/docs', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_all_docs'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Inicializa las configuraciones de todos los módulos
     */
    private function init_modules_config() {
        $this->modules_config = array(
            'eventos' => array(
                'id'       => 'eventos',
                'titulo'   => 'Eventos',
                'icono'    => 'event',
                'endpoint' => '/flavor/v1/eventos',
                'layout'   => 'card',
                'campos'   => array(
                    'titulo'      => 'titulo',
                    'subtitulo'   => 'lugar',
                    'descripcion' => 'descripcion',
                    'imagen'      => 'imagen',
                    'estado'      => 'estado',
                    'fecha'       => 'fecha_inicio',
                    'badge'       => 'categoria',
                ),
                'acciones' => array(
                    array(
                        'id'       => 'inscribirse',
                        'label'    => 'Inscribirse',
                        'icono'    => 'person_add',
                        'tipo'     => 'api_call',
                        'endpoint' => '/flavor/v1/eventos/{id}/inscribirse',
                    ),
                    array(
                        'id'       => 'compartir',
                        'label'    => 'Compartir',
                        'icono'    => 'share',
                        'tipo'     => 'share',
                    ),
                ),
                'fab' => array(
                    'icono'  => 'add',
                    'label'  => 'Nuevo evento',
                    'accion' => 'create',
                ),
                'filtros' => array(
                    array(
                        'id'       => 'estado',
                        'label'    => 'Estado',
                        'tipo'     => 'select',
                        'opciones' => array(
                            array('value' => '',          'label' => 'Todos'),
                            array('value' => 'activo',    'label' => 'Activos'),
                            array('value' => 'proximo',   'label' => 'Próximos'),
                            array('value' => 'finalizado','label' => 'Finalizados'),
                        ),
                    ),
                ),
                'form_fields' => array(
                    array('name' => 'titulo',       'label' => 'Título',       'type' => 'text',     'required' => true),
                    array('name' => 'descripcion',  'label' => 'Descripción',  'type' => 'textarea', 'required' => true),
                    array('name' => 'fecha_inicio', 'label' => 'Fecha inicio', 'type' => 'date',     'required' => true),
                    array('name' => 'fecha_fin',    'label' => 'Fecha fin',    'type' => 'date'),
                    array('name' => 'hora_inicio',  'label' => 'Hora inicio',  'type' => 'time'),
                    array('name' => 'hora_fin',     'label' => 'Hora fin',     'type' => 'time'),
                    array('name' => 'lugar',        'label' => 'Lugar',        'type' => 'text'),
                    array('name' => 'precio',       'label' => 'Precio',       'type' => 'number', 'min' => 0, 'suffix' => '€'),
                    array('name' => 'capacidad',    'label' => 'Capacidad',    'type' => 'number', 'min' => 1),
                    array('name' => 'imagen',       'label' => 'Imagen',       'type' => 'image'),
                ),
            ),

            'espacios' => array(
                'id'       => 'espacios',
                'titulo'   => 'Espacios Comunes',
                'icono'    => 'meeting_room',
                'endpoint' => '/flavor/v1/espacios-comunes',
                'layout'   => 'grid',
                'campos'   => array(
                    'titulo'  => 'nombre',
                    'imagen'  => 'imagen',
                    'estado'  => 'estado',
                    'badge'   => 'capacidad',
                ),
                'acciones' => array(
                    array(
                        'id'       => 'reservar',
                        'label'    => 'Reservar',
                        'icono'    => 'calendar_today',
                        'tipo'     => 'navigate',
                        'route'    => '/reservas/crear',
                    ),
                ),
                'filtros' => array(
                    array(
                        'id'       => 'tipo',
                        'label'    => 'Tipo',
                        'tipo'     => 'select',
                        'opciones' => array(
                            array('value' => '',           'label' => 'Todos'),
                            array('value' => 'sala',       'label' => 'Sala'),
                            array('value' => 'pista',      'label' => 'Pista deportiva'),
                            array('value' => 'jardin',     'label' => 'Jardín'),
                            array('value' => 'parking',    'label' => 'Parking'),
                        ),
                    ),
                ),
            ),

            'incidencias' => array(
                'id'       => 'incidencias',
                'titulo'   => 'Incidencias',
                'icono'    => 'report_problem',
                'endpoint' => '/flavor/v1/incidencias',
                'layout'   => 'list',
                'campos'   => array(
                    'titulo'    => 'titulo',
                    'subtitulo' => 'ubicacion',
                    'estado'    => 'estado',
                ),
                'acciones' => array(
                    array(
                        'id'       => 'ver',
                        'label'    => 'Ver detalles',
                        'icono'    => 'visibility',
                        'tipo'     => 'navigate',
                    ),
                ),
                'fab' => array(
                    'icono'  => 'add_alert',
                    'label'  => 'Reportar incidencia',
                    'accion' => 'create',
                ),
                'filtros' => array(
                    array(
                        'id'       => 'estado',
                        'label'    => 'Estado',
                        'tipo'     => 'select',
                        'opciones' => array(
                            array('value' => '',           'label' => 'Todas'),
                            array('value' => 'pendiente', 'label' => 'Pendiente'),
                            array('value' => 'en_proceso','label' => 'En proceso'),
                            array('value' => 'resuelta',  'label' => 'Resuelta'),
                        ),
                    ),
                ),
                'form_fields' => array(
                    array('name' => 'titulo',      'label' => 'Título',      'type' => 'text',     'required' => true),
                    array('name' => 'descripcion', 'label' => 'Descripción', 'type' => 'textarea', 'required' => true),
                    array('name' => 'ubicacion',   'label' => 'Ubicación',   'type' => 'text'),
                    array('name' => 'categoria',   'label' => 'Categoría',   'type' => 'select', 'options' => array(
                        array('value' => 'averia',    'label' => 'Avería'),
                        array('value' => 'limpieza',  'label' => 'Limpieza'),
                        array('value' => 'ruido',     'label' => 'Ruido'),
                        array('value' => 'seguridad', 'label' => 'Seguridad'),
                        array('value' => 'otro',      'label' => 'Otro'),
                    )),
                    array('name' => 'imagen',      'label' => 'Foto',        'type' => 'image'),
                ),
            ),

            'banco_tiempo' => array(
                'id'       => 'banco_tiempo',
                'titulo'   => 'Banco de Tiempo',
                'icono'    => 'schedule',
                'endpoint' => '/flavor/v1/banco-tiempo/servicios',
                'layout'   => 'card',
                'campos'   => array(
                    'titulo'      => 'nombre',
                    'subtitulo'   => 'usuario_nombre',
                    'descripcion' => 'descripcion',
                    'badge'       => 'categoria',
                ),
                'acciones' => array(
                    array(
                        'id'                    => 'solicitar',
                        'label'                 => 'Solicitar',
                        'icono'                 => 'swap_horiz',
                        'tipo'                  => 'api_call',
                        'endpoint'              => '/flavor/v1/banco-tiempo/intercambios',
                        'requiere_confirmacion' => true,
                    ),
                ),
                'fab' => array(
                    'icono'  => 'add',
                    'label'  => 'Ofrecer servicio',
                    'accion' => 'create',
                ),
            ),

            'grupos_consumo' => array(
                'id'       => 'grupos_consumo',
                'titulo'   => 'Grupos de Consumo',
                'icono'    => 'shopping_basket',
                'endpoint' => '/flavor/v1/grupos-consumo/productos',
                'layout'   => 'grid',
                'campos'   => array(
                    'titulo'  => 'nombre',
                    'imagen'  => 'imagen',
                    'badge'   => 'precio',
                ),
                'acciones' => array(
                    array(
                        'id'       => 'añadir_cesta',
                        'label'    => 'Añadir a cesta',
                        'icono'    => 'add_shopping_cart',
                        'tipo'     => 'api_call',
                        'endpoint' => '/flavor/v1/grupos-consumo/cesta/add',
                    ),
                ),
                'filtros' => array(
                    array(
                        'id'       => 'categoria',
                        'label'    => 'Categoría',
                        'tipo'     => 'select',
                        'opciones' => array(
                            array('value' => '',          'label' => 'Todas'),
                            array('value' => 'verduras',  'label' => 'Verduras'),
                            array('value' => 'frutas',    'label' => 'Frutas'),
                            array('value' => 'lacteos',   'label' => 'Lácteos'),
                            array('value' => 'panaderia', 'label' => 'Panadería'),
                        ),
                    ),
                ),
            ),

            'biblioteca' => array(
                'id'       => 'biblioteca',
                'titulo'   => 'Biblioteca',
                'icono'    => 'local_library',
                'endpoint' => '/flavor/v1/biblioteca',
                'layout'   => 'grid',
                'campos'   => array(
                    'titulo'    => 'titulo',
                    'subtitulo' => 'autor',
                    'imagen'    => 'portada',
                    'estado'    => 'disponible',
                ),
                'acciones' => array(
                    array(
                        'id'       => 'reservar',
                        'label'    => 'Reservar',
                        'icono'    => 'book',
                        'tipo'     => 'api_call',
                        'endpoint' => '/flavor/v1/biblioteca/{id}/reservar',
                    ),
                ),
                'filtros' => array(
                    array(
                        'id'       => 'categoria',
                        'label'    => 'Categoría',
                        'tipo'     => 'select',
                        'opciones' => array(
                            array('value' => '',          'label' => 'Todas'),
                            array('value' => 'novela',    'label' => 'Novela'),
                            array('value' => 'ensayo',    'label' => 'Ensayo'),
                            array('value' => 'infantil',  'label' => 'Infantil'),
                            array('value' => 'tecnico',   'label' => 'Técnico'),
                        ),
                    ),
                ),
            ),

            'avisos' => array(
                'id'       => 'avisos',
                'titulo'   => 'Avisos',
                'icono'    => 'campaign',
                'endpoint' => '/flavor/v1/avisos',
                'layout'   => 'list',
                'campos'   => array(
                    'titulo'    => 'titulo',
                    'subtitulo' => 'fecha',
                    'estado'    => 'tipo',
                ),
                'empty_message' => 'No hay avisos recientes',
                'empty_icon'    => 'notifications_off',
            ),

            'marketplace' => array(
                'id'       => 'marketplace',
                'titulo'   => 'Marketplace',
                'icono'    => 'storefront',
                'endpoint' => '/flavor/v1/marketplace',
                'layout'   => 'grid',
                'campos'   => array(
                    'titulo'  => 'titulo',
                    'imagen'  => 'imagen',
                    'badge'   => 'precio',
                    'estado'  => 'estado',
                ),
                'fab' => array(
                    'icono'  => 'sell',
                    'label'  => 'Publicar anuncio',
                    'accion' => 'create',
                ),
                'filtros' => array(
                    array(
                        'id'       => 'tipo',
                        'label'    => 'Tipo',
                        'tipo'     => 'select',
                        'opciones' => array(
                            array('value' => '',       'label' => 'Todos'),
                            array('value' => 'venta',  'label' => 'Venta'),
                            array('value' => 'regalo', 'label' => 'Regalo'),
                            array('value' => 'busco',  'label' => 'Busco'),
                        ),
                    ),
                ),
            ),

            'huertos' => array(
                'id'       => 'huertos',
                'titulo'   => 'Huertos Urbanos',
                'icono'    => 'grass',
                'endpoint' => '/flavor/v1/huertos',
                'layout'   => 'grid',
                'campos'   => array(
                    'titulo'  => 'nombre',
                    'imagen'  => 'imagen',
                    'estado'  => 'estado',
                    'badge'   => 'superficie',
                ),
                'acciones' => array(
                    array(
                        'id'       => 'solicitar',
                        'label'    => 'Solicitar parcela',
                        'icono'    => 'spa',
                        'tipo'     => 'navigate',
                        'route'    => '/huertos/solicitar',
                    ),
                ),
            ),

            'foros' => array(
                'id'       => 'foros',
                'titulo'   => 'Foros',
                'icono'    => 'forum',
                'endpoint' => '/flavor/v1/foros',
                'layout'   => 'list',
                'campos'   => array(
                    'titulo'    => 'titulo',
                    'subtitulo' => 'ultimo_mensaje',
                    'badge'     => 'respuestas',
                ),
                'fab' => array(
                    'icono'  => 'add_comment',
                    'label'  => 'Nuevo tema',
                    'accion' => 'create',
                ),
            ),

            'carpooling' => array(
                'id'       => 'carpooling',
                'titulo'   => 'Compartir Coche',
                'icono'    => 'directions_car',
                'endpoint' => '/flavor/v1/carpooling',
                'layout'   => 'card',
                'campos'   => array(
                    'titulo'    => 'ruta',
                    'subtitulo' => 'conductor_nombre',
                    'fecha'     => 'fecha_salida',
                    'badge'     => 'plazas_disponibles',
                ),
                'acciones' => array(
                    array(
                        'id'       => 'reservar_plaza',
                        'label'    => 'Reservar plaza',
                        'icono'    => 'airline_seat_recline_normal',
                        'tipo'     => 'api_call',
                        'endpoint' => '/flavor/v1/carpooling/{id}/reservar',
                    ),
                ),
                'fab' => array(
                    'icono'  => 'add',
                    'label'  => 'Publicar viaje',
                    'accion' => 'create',
                ),
                'form_fields' => array(
                    array('name' => 'origen',       'label' => 'Origen',      'type' => 'text',   'required' => true),
                    array('name' => 'destino',      'label' => 'Destino',     'type' => 'text',   'required' => true),
                    array('name' => 'fecha_salida', 'label' => 'Fecha',       'type' => 'date',   'required' => true),
                    array('name' => 'hora_salida',  'label' => 'Hora',        'type' => 'time',   'required' => true),
                    array('name' => 'plazas',       'label' => 'Plazas',      'type' => 'number', 'required' => true, 'min' => 1, 'max' => 7),
                    array('name' => 'precio',       'label' => 'Precio/plaza','type' => 'number', 'min' => 0, 'suffix' => '€'),
                    array('name' => 'notas',        'label' => 'Notas',       'type' => 'textarea'),
                ),
            ),

            'bicicletas' => array(
                'id'       => 'bicicletas',
                'titulo'   => 'Bicicletas Compartidas',
                'icono'    => 'pedal_bike',
                'endpoint' => '/flavor/v1/bicicletas',
                'layout'   => 'grid',
                'campos'   => array(
                    'titulo'  => 'nombre',
                    'imagen'  => 'imagen',
                    'estado'  => 'disponible',
                    'badge'   => 'ubicacion',
                ),
                'acciones' => array(
                    array(
                        'id'       => 'alquilar',
                        'label'    => 'Alquilar',
                        'icono'    => 'lock_open',
                        'tipo'     => 'api_call',
                        'endpoint' => '/flavor/v1/bicicletas/{id}/alquilar',
                    ),
                ),
            ),

            'compostaje' => array(
                'id'       => 'compostaje',
                'titulo'   => 'Compostaje',
                'icono'    => 'compost',
                'endpoint' => '/flavor/v1/compostaje',
                'layout'   => 'dashboard',
                'campos'   => array(
                    'titulo' => 'nombre',
                    'estado' => 'estado',
                ),
                'acciones' => array(
                    array(
                        'id'       => 'registrar_aporte',
                        'label'    => 'Registrar aporte',
                        'icono'    => 'add_circle',
                        'tipo'     => 'navigate',
                        'route'    => '/compostaje/aportar',
                    ),
                ),
            ),

            'ayuda_vecinal' => array(
                'id'       => 'ayuda_vecinal',
                'titulo'   => 'Ayuda Vecinal',
                'icono'    => 'volunteer_activism',
                'endpoint' => '/flavor/v1/ayuda-vecinal',
                'layout'   => 'card',
                'campos'   => array(
                    'titulo'      => 'titulo',
                    'descripcion' => 'descripcion',
                    'estado'      => 'estado',
                    'badge'       => 'tipo',
                ),
                'fab' => array(
                    'icono'  => 'add',
                    'label'  => 'Pedir ayuda',
                    'accion' => 'create',
                ),
                'form_fields' => array(
                    array('name' => 'titulo',      'label' => 'Título',      'type' => 'text',     'required' => true),
                    array('name' => 'descripcion', 'label' => 'Descripción', 'type' => 'textarea', 'required' => true),
                    array('name' => 'tipo',        'label' => 'Tipo',        'type' => 'select', 'options' => array(
                        array('value' => 'ofrezco', 'label' => 'Ofrezco ayuda'),
                        array('value' => 'necesito','label' => 'Necesito ayuda'),
                    )),
                    array('name' => 'categoria',   'label' => 'Categoría',   'type' => 'select', 'options' => array(
                        array('value' => 'compras',   'label' => 'Compras'),
                        array('value' => 'transporte','label' => 'Transporte'),
                        array('value' => 'cuidados',  'label' => 'Cuidados'),
                        array('value' => 'reparacion','label' => 'Reparación'),
                        array('value' => 'otro',      'label' => 'Otro'),
                    )),
                ),
            ),

            'recursos_compartidos' => array(
                'id'       => 'recursos_compartidos',
                'titulo'   => 'Recursos Compartidos',
                'icono'    => 'handyman',
                'endpoint' => '/flavor/v1/recursos-compartidos',
                'layout'   => 'grid',
                'campos'   => array(
                    'titulo'  => 'nombre',
                    'imagen'  => 'imagen',
                    'estado'  => 'disponible',
                    'badge'   => 'categoria',
                ),
                'acciones' => array(
                    array(
                        'id'       => 'solicitar',
                        'label'    => 'Solicitar préstamo',
                        'icono'    => 'assignment_return',
                        'tipo'     => 'navigate',
                        'route'    => '/recursos/{id}/solicitar',
                    ),
                ),
                'fab' => array(
                    'icono'  => 'add',
                    'label'  => 'Compartir recurso',
                    'accion' => 'create',
                ),
            ),

            'participacion' => array(
                'id'       => 'participacion',
                'titulo'   => 'Participación',
                'icono'    => 'how_to_vote',
                'endpoint' => '/flavor/v1/participacion',
                'layout'   => 'card',
                'campos'   => array(
                    'titulo'      => 'titulo',
                    'descripcion' => 'descripcion',
                    'estado'      => 'estado',
                    'fecha'       => 'fecha_fin',
                ),
                'acciones' => array(
                    array(
                        'id'    => 'votar',
                        'label' => 'Participar',
                        'icono' => 'how_to_vote',
                        'tipo'  => 'navigate',
                        'route' => '/participacion/{id}/votar',
                    ),
                ),
            ),

            'presupuestos' => array(
                'id'       => 'presupuestos',
                'titulo'   => 'Presupuestos Participativos',
                'icono'    => 'savings',
                'endpoint' => '/flavor/v1/presupuestos-participativos',
                'layout'   => 'card',
                'campos'   => array(
                    'titulo'      => 'titulo',
                    'descripcion' => 'descripcion',
                    'badge'       => 'presupuesto',
                    'estado'      => 'estado',
                ),
                'acciones' => array(
                    array(
                        'id'    => 'votar',
                        'label' => 'Votar propuesta',
                        'icono' => 'thumb_up',
                        'tipo'  => 'api_call',
                        'endpoint' => '/flavor/v1/presupuestos/{id}/votar',
                    ),
                ),
                'fab' => array(
                    'icono'  => 'lightbulb',
                    'label'  => 'Nueva propuesta',
                    'accion' => 'create',
                ),
            ),

            'radio' => array(
                'id'       => 'radio',
                'titulo'   => 'Radio Comunitaria',
                'icono'    => 'radio',
                'endpoint' => '/flavor/v1/radio/programacion',
                'layout'   => 'list',
                'campos'   => array(
                    'titulo'    => 'nombre',
                    'subtitulo' => 'horario',
                    'imagen'    => 'imagen',
                ),
            ),

            'podcast' => array(
                'id'       => 'podcast',
                'titulo'   => 'Podcasts',
                'icono'    => 'podcasts',
                'endpoint' => '/flavor/v1/podcast',
                'layout'   => 'card',
                'campos'   => array(
                    'titulo'      => 'titulo',
                    'subtitulo'   => 'programa',
                    'descripcion' => 'descripcion',
                    'imagen'      => 'portada',
                    'fecha'       => 'fecha_publicacion',
                ),
            ),

            'transparencia' => array(
                'id'       => 'transparencia',
                'titulo'   => 'Transparencia',
                'icono'    => 'visibility',
                'endpoint' => '/flavor/v1/transparencia',
                'layout'   => 'list',
                'campos'   => array(
                    'titulo'    => 'titulo',
                    'subtitulo' => 'categoria',
                    'fecha'     => 'fecha',
                ),
            ),

            // === MÓDULOS ADICIONALES ===

            'woocommerce' => array(
                'id'       => 'woocommerce',
                'titulo'   => 'Tienda',
                'icono'    => 'local_offer',
                'endpoint' => '/flavor/v1/woocommerce/products',
                'layout'   => 'grid',
                'campos'   => array(
                    'titulo'  => 'name',
                    'imagen'  => 'image',
                    'badge'   => 'price',
                    'estado'  => 'stock_status',
                ),
                'acciones' => array(
                    array(
                        'id'       => 'add_cart',
                        'label'    => 'Añadir al carrito',
                        'icono'    => 'add_shopping_cart',
                        'tipo'     => 'api_call',
                        'endpoint' => '/flavor/v1/woocommerce/cart/add',
                    ),
                ),
                'filtros' => array(
                    array(
                        'id'       => 'categoria',
                        'label'    => 'Categoría',
                        'tipo'     => 'select',
                        'opciones' => array(),
                    ),
                ),
            ),

            'facturas' => array(
                'id'       => 'facturas',
                'titulo'   => 'Facturas',
                'icono'    => 'receipt',
                'endpoint' => '/flavor/v1/facturas',
                'layout'   => 'list',
                'requiere_auth' => true,
                'campos'   => array(
                    'titulo'    => 'numero_factura',
                    'subtitulo' => 'cliente_nombre',
                    'fecha'     => 'fecha',
                    'badge'     => 'total',
                    'estado'    => 'estado',
                ),
                'acciones' => array(
                    array(
                        'id'    => 'descargar',
                        'label' => 'Descargar PDF',
                        'icono' => 'download',
                        'tipo'  => 'api_call',
                        'endpoint' => '/flavor/v1/facturas/{id}/pdf',
                    ),
                ),
                'fab' => array(
                    'icono'  => 'add',
                    'label'  => 'Nueva factura',
                    'accion' => 'create',
                ),
            ),

            'fichaje' => array(
                'id'       => 'fichaje',
                'titulo'   => 'Fichaje',
                'icono'    => 'work',
                'endpoint' => '/flavor/v1/fichaje',
                'layout'   => 'dashboard',
                'requiere_auth' => true,
                'campos'   => array(
                    'titulo' => 'fecha',
                    'badge'  => 'horas_trabajadas',
                ),
                'acciones' => array(
                    array(
                        'id'    => 'fichar_entrada',
                        'label' => 'Fichar entrada',
                        'icono' => 'login',
                        'tipo'  => 'api_call',
                        'endpoint' => '/flavor/v1/fichaje/entrada',
                    ),
                    array(
                        'id'    => 'fichar_salida',
                        'label' => 'Fichar salida',
                        'icono' => 'logout',
                        'tipo'  => 'api_call',
                        'endpoint' => '/flavor/v1/fichaje/salida',
                    ),
                ),
            ),

            'socios' => array(
                'id'       => 'socios',
                'titulo'   => 'Gestión de Socios',
                'icono'    => 'people',
                'endpoint' => '/flavor/v1/socios',
                'layout'   => 'list',
                'campos'   => array(
                    'titulo'    => 'nombre_completo',
                    'subtitulo' => 'numero_socio',
                    'imagen'    => 'avatar',
                    'estado'    => 'estado',
                ),
                'filtros' => array(
                    array(
                        'id'       => 'estado',
                        'label'    => 'Estado',
                        'tipo'     => 'select',
                        'opciones' => array(
                            array('value' => '',       'label' => 'Todos'),
                            array('value' => 'activo', 'label' => 'Activos'),
                            array('value' => 'baja',   'label' => 'Baja'),
                            array('value' => 'moroso', 'label' => 'Morosos'),
                        ),
                    ),
                ),
                'fab' => array(
                    'icono'  => 'person_add',
                    'label'  => 'Nuevo socio',
                    'accion' => 'create',
                ),
            ),

            'publicidad' => array(
                'id'       => 'publicidad',
                'titulo'   => 'Publicidad Ética',
                'icono'    => 'campaign',
                'endpoint' => '/flavor/v1/publicidad',
                'layout'   => 'card',
                'campos'   => array(
                    'titulo'      => 'titulo',
                    'descripcion' => 'descripcion',
                    'imagen'      => 'imagen',
                    'badge'       => 'anunciante',
                ),
            ),

            'red_social' => array(
                'id'       => 'red_social',
                'titulo'   => 'Red Social',
                'icono'    => 'public',
                'endpoint' => '/flavor/v1/red-social/posts',
                'layout'   => 'card',
                'campos'   => array(
                    'titulo'      => 'autor_nombre',
                    'descripcion' => 'contenido',
                    'imagen'      => 'imagen',
                    'fecha'       => 'fecha',
                    'badge'       => 'likes',
                ),
                'acciones' => array(
                    array(
                        'id'    => 'like',
                        'label' => 'Me gusta',
                        'icono' => 'favorite',
                        'tipo'  => 'api_call',
                        'endpoint' => '/flavor/v1/red-social/posts/{id}/like',
                    ),
                    array(
                        'id'    => 'comentar',
                        'label' => 'Comentar',
                        'icono' => 'comment',
                        'tipo'  => 'navigate',
                        'route' => '/red-social/posts/{id}/comentarios',
                    ),
                ),
                'fab' => array(
                    'icono'  => 'edit',
                    'label'  => 'Nueva publicación',
                    'accion' => 'create',
                ),
            ),

            'chat_grupos' => array(
                'id'       => 'chat_grupos',
                'titulo'   => 'Chat de Grupos',
                'icono'    => 'chat',
                'endpoint' => '/flavor/v1/chat/grupos',
                'layout'   => 'list',
                'campos'   => array(
                    'titulo'    => 'nombre',
                    'subtitulo' => 'ultimo_mensaje',
                    'imagen'    => 'avatar',
                    'badge'     => 'no_leidos',
                ),
                'acciones' => array(
                    array(
                        'id'    => 'entrar',
                        'label' => 'Entrar al chat',
                        'icono' => 'chat_bubble',
                        'tipo'  => 'navigate',
                        'route' => '/chat/grupos/{id}',
                    ),
                ),
            ),

            'chat_interno' => array(
                'id'       => 'chat_interno',
                'titulo'   => 'Mensajes',
                'icono'    => 'chat_bubble',
                'endpoint' => '/flavor/v1/chat/conversaciones',
                'layout'   => 'list',
                'campos'   => array(
                    'titulo'    => 'contacto_nombre',
                    'subtitulo' => 'ultimo_mensaje',
                    'imagen'    => 'contacto_avatar',
                    'badge'     => 'no_leidos',
                ),
            ),

            'comunidades' => array(
                'id'       => 'comunidades',
                'titulo'   => 'Comunidades',
                'icono'    => 'groups',
                'endpoint' => '/flavor/v1/comunidades',
                'layout'   => 'card',
                'campos'   => array(
                    'titulo'      => 'nombre',
                    'descripcion' => 'descripcion',
                    'imagen'      => 'logo',
                    'badge'       => 'miembros',
                ),
                'acciones' => array(
                    array(
                        'id'    => 'unirse',
                        'label' => 'Unirse',
                        'icono' => 'group_add',
                        'tipo'  => 'api_call',
                        'endpoint' => '/flavor/v1/comunidades/{id}/unirse',
                    ),
                ),
            ),

            'colectivos' => array(
                'id'       => 'colectivos',
                'titulo'   => 'Colectivos',
                'icono'    => 'handshake',
                'endpoint' => '/flavor/v1/colectivos',
                'layout'   => 'card',
                'campos'   => array(
                    'titulo'      => 'nombre',
                    'descripcion' => 'descripcion',
                    'imagen'      => 'logo',
                    'badge'       => 'tipo',
                ),
                'filtros' => array(
                    array(
                        'id'       => 'tipo',
                        'label'    => 'Tipo',
                        'tipo'     => 'select',
                        'opciones' => array(
                            array('value' => '',            'label' => 'Todos'),
                            array('value' => 'asociacion',  'label' => 'Asociación'),
                            array('value' => 'cooperativa', 'label' => 'Cooperativa'),
                            array('value' => 'fundacion',   'label' => 'Fundación'),
                        ),
                    ),
                ),
            ),

            'tramites' => array(
                'id'       => 'tramites',
                'titulo'   => 'Trámites',
                'icono'    => 'assignment',
                'endpoint' => '/flavor/v1/tramites',
                'layout'   => 'list',
                'campos'   => array(
                    'titulo'    => 'titulo',
                    'subtitulo' => 'tipo',
                    'fecha'     => 'fecha_solicitud',
                    'estado'    => 'estado',
                ),
                'filtros' => array(
                    array(
                        'id'       => 'estado',
                        'label'    => 'Estado',
                        'tipo'     => 'select',
                        'opciones' => array(
                            array('value' => '',           'label' => 'Todos'),
                            array('value' => 'pendiente', 'label' => 'Pendiente'),
                            array('value' => 'en_proceso','label' => 'En proceso'),
                            array('value' => 'completado','label' => 'Completado'),
                            array('value' => 'rechazado', 'label' => 'Rechazado'),
                        ),
                    ),
                ),
                'fab' => array(
                    'icono'  => 'add',
                    'label'  => 'Nuevo trámite',
                    'accion' => 'create',
                ),
            ),

            'reciclaje' => array(
                'id'       => 'reciclaje',
                'titulo'   => 'Reciclaje',
                'icono'    => 'recycling',
                'endpoint' => '/flavor/v1/reciclaje',
                'layout'   => 'dashboard',
                'campos'   => array(
                    'titulo' => 'tipo',
                    'badge'  => 'puntos',
                ),
                'acciones' => array(
                    array(
                        'id'    => 'registrar',
                        'label' => 'Registrar reciclaje',
                        'icono' => 'qr_code_scanner',
                        'tipo'  => 'navigate',
                        'route' => '/reciclaje/registrar',
                    ),
                ),
            ),

            'cursos' => array(
                'id'       => 'cursos',
                'titulo'   => 'Cursos',
                'icono'    => 'menu_book',
                'endpoint' => '/flavor/v1/cursos',
                'layout'   => 'card',
                'campos'   => array(
                    'titulo'      => 'titulo',
                    'descripcion' => 'descripcion',
                    'imagen'      => 'imagen',
                    'badge'       => 'duracion',
                    'estado'      => 'estado',
                ),
                'acciones' => array(
                    array(
                        'id'    => 'inscribirse',
                        'label' => 'Inscribirse',
                        'icono' => 'school',
                        'tipo'  => 'api_call',
                        'endpoint' => '/flavor/v1/cursos/{id}/inscribirse',
                    ),
                ),
                'filtros' => array(
                    array(
                        'id'       => 'categoria',
                        'label'    => 'Categoría',
                        'tipo'     => 'select',
                        'opciones' => array(
                            array('value' => '',           'label' => 'Todas'),
                            array('value' => 'idiomas',    'label' => 'Idiomas'),
                            array('value' => 'informatica','label' => 'Informática'),
                            array('value' => 'arte',       'label' => 'Arte'),
                            array('value' => 'oficios',    'label' => 'Oficios'),
                        ),
                    ),
                ),
            ),

            'multimedia' => array(
                'id'       => 'multimedia',
                'titulo'   => 'Multimedia',
                'icono'    => 'perm_media',
                'endpoint' => '/flavor/v1/multimedia',
                'layout'   => 'grid',
                'campos'   => array(
                    'titulo'  => 'titulo',
                    'imagen'  => 'thumbnail',
                    'badge'   => 'tipo',
                ),
                'filtros' => array(
                    array(
                        'id'       => 'tipo',
                        'label'    => 'Tipo',
                        'tipo'     => 'select',
                        'opciones' => array(
                            array('value' => '',       'label' => 'Todos'),
                            array('value' => 'imagen', 'label' => 'Imágenes'),
                            array('value' => 'video',  'label' => 'Videos'),
                            array('value' => 'audio',  'label' => 'Audio'),
                        ),
                    ),
                ),
            ),

            'talleres' => array(
                'id'       => 'talleres',
                'titulo'   => 'Talleres',
                'icono'    => 'build',
                'endpoint' => '/flavor/v1/talleres',
                'layout'   => 'card',
                'campos'   => array(
                    'titulo'      => 'titulo',
                    'descripcion' => 'descripcion',
                    'imagen'      => 'imagen',
                    'fecha'       => 'fecha',
                    'badge'       => 'plazas_disponibles',
                ),
                'acciones' => array(
                    array(
                        'id'    => 'inscribirse',
                        'label' => 'Inscribirse',
                        'icono' => 'how_to_reg',
                        'tipo'  => 'api_call',
                        'endpoint' => '/flavor/v1/talleres/{id}/inscribirse',
                    ),
                ),
            ),

            'parkings' => array(
                'id'       => 'parkings',
                'titulo'   => 'Parkings',
                'icono'    => 'local_parking',
                'endpoint' => '/flavor/v1/parkings',
                'layout'   => 'grid',
                'campos'   => array(
                    'titulo'  => 'nombre',
                    'imagen'  => 'imagen',
                    'estado'  => 'disponible',
                    'badge'   => 'plazas_libres',
                ),
                'acciones' => array(
                    array(
                        'id'    => 'reservar',
                        'label' => 'Reservar plaza',
                        'icono' => 'event_available',
                        'tipo'  => 'navigate',
                        'route' => '/parkings/{id}/reservar',
                    ),
                ),
            ),

            'empresarial' => array(
                'id'       => 'empresarial',
                'titulo'   => 'Empresarial',
                'icono'    => 'business',
                'endpoint' => '/flavor/v1/empresarial',
                'layout'   => 'dashboard',
                'campos'   => array(
                    'titulo' => 'nombre',
                    'badge'  => 'tipo',
                ),
            ),

            'clientes' => array(
                'id'       => 'clientes',
                'titulo'   => 'Clientes',
                'icono'    => 'person',
                'endpoint' => '/flavor/v1/clientes',
                'layout'   => 'list',
                'campos'   => array(
                    'titulo'    => 'nombre',
                    'subtitulo' => 'email',
                    'imagen'    => 'avatar',
                    'badge'     => 'total_compras',
                ),
                'fab' => array(
                    'icono'  => 'person_add',
                    'label'  => 'Nuevo cliente',
                    'accion' => 'create',
                ),
                'form_fields' => array(
                    array('name' => 'nombre',   'label' => 'Nombre',   'type' => 'text',  'required' => true),
                    array('name' => 'email',    'label' => 'Email',    'type' => 'email', 'required' => true),
                    array('name' => 'telefono', 'label' => 'Teléfono', 'type' => 'phone'),
                    array('name' => 'direccion','label' => 'Dirección','type' => 'text'),
                    array('name' => 'notas',    'label' => 'Notas',    'type' => 'textarea'),
                ),
            ),

            'bares' => array(
                'id'       => 'bares',
                'titulo'   => 'Bares y Hostelería',
                'icono'    => 'restaurant',
                'endpoint' => '/flavor/v1/bares',
                'layout'   => 'card',
                'campos'   => array(
                    'titulo'      => 'nombre',
                    'descripcion' => 'descripcion',
                    'imagen'      => 'imagen',
                    'badge'       => 'tipo',
                    'estado'      => 'abierto',
                ),
                'acciones' => array(
                    array(
                        'id'    => 'llamar',
                        'label' => 'Llamar',
                        'icono' => 'phone',
                        'tipo'  => 'call',
                    ),
                    array(
                        'id'    => 'ubicacion',
                        'label' => 'Ver ubicación',
                        'icono' => 'map',
                        'tipo'  => 'map',
                    ),
                ),
            ),

            'trading_ia' => array(
                'id'       => 'trading_ia',
                'titulo'   => 'Trading IA',
                'icono'    => 'trending_up',
                'endpoint' => '/flavor/v1/trading-ia',
                'layout'   => 'dashboard',
                'campos'   => array(
                    'titulo' => 'par',
                    'badge'  => 'precio',
                ),
            ),

            'dex_solana' => array(
                'id'       => 'dex_solana',
                'titulo'   => 'DEX Solana',
                'icono'    => 'currency_exchange',
                'endpoint' => '/flavor/v1/dex-solana',
                'layout'   => 'dashboard',
                'campos'   => array(
                    'titulo' => 'token',
                    'badge'  => 'precio',
                ),
            ),

            'themacle' => array(
                'id'       => 'themacle',
                'titulo'   => 'Themacle',
                'icono'    => 'palette',
                'endpoint' => '/flavor/v1/themacle',
                'layout'   => 'grid',
                'campos'   => array(
                    'titulo'  => 'nombre',
                    'imagen'  => 'preview',
                    'badge'   => 'categoria',
                ),
            ),

            'reservas' => array(
                'id'       => 'reservas',
                'titulo'   => 'Mis Reservas',
                'icono'    => 'calendar_today',
                'endpoint' => '/flavor/v1/reservas',
                'layout'   => 'list',
                'campos'   => array(
                    'titulo'    => 'espacio_nombre',
                    'subtitulo' => 'fecha_hora',
                    'estado'    => 'estado',
                ),
                'filtros' => array(
                    array(
                        'id'       => 'estado',
                        'label'    => 'Estado',
                        'tipo'     => 'select',
                        'opciones' => array(
                            array('value' => '',          'label' => 'Todas'),
                            array('value' => 'pendiente','label' => 'Pendientes'),
                            array('value' => 'confirmada','label' => 'Confirmadas'),
                            array('value' => 'cancelada','label' => 'Canceladas'),
                        ),
                    ),
                ),
            ),

            'email_marketing' => array(
                'id'       => 'email_marketing',
                'titulo'   => 'Email Marketing',
                'icono'    => 'email',
                'endpoint' => '/flavor/v1/email-marketing/campaigns',
                'layout'   => 'list',
                'campos'   => array(
                    'titulo'    => 'asunto',
                    'subtitulo' => 'estado',
                    'fecha'     => 'fecha_envio',
                    'badge'     => 'destinatarios',
                ),
            ),

            'sello_conciencia' => array(
                'id'       => 'sello_conciencia',
                'titulo'   => 'Sello Conciencia',
                'icono'    => 'verified',
                'endpoint' => '/flavor/v1/sello-conciencia',
                'layout'   => 'card',
                'campos'   => array(
                    'titulo'      => 'nombre',
                    'descripcion' => 'descripcion',
                    'imagen'      => 'logo',
                    'badge'       => 'nivel',
                ),
            ),

            'circulos_cuidados' => array(
                'id'       => 'circulos_cuidados',
                'titulo'   => 'Círculos de Cuidados',
                'icono'    => 'favorite',
                'endpoint' => '/flavor/v1/circulos-cuidados',
                'layout'   => 'card',
                'campos'   => array(
                    'titulo'      => 'nombre',
                    'descripcion' => 'descripcion',
                    'badge'       => 'miembros',
                ),
                'acciones' => array(
                    array(
                        'id'    => 'unirse',
                        'label' => 'Unirse al círculo',
                        'icono' => 'group_add',
                        'tipo'  => 'api_call',
                        'endpoint' => '/flavor/v1/circulos-cuidados/{id}/unirse',
                    ),
                ),
            ),

            'economia_don' => array(
                'id'       => 'economia_don',
                'titulo'   => 'Economía del Don',
                'icono'    => 'card_giftcard',
                'endpoint' => '/flavor/v1/economia-don',
                'layout'   => 'card',
                'campos'   => array(
                    'titulo'      => 'titulo',
                    'descripcion' => 'descripcion',
                    'imagen'      => 'imagen',
                    'badge'       => 'tipo',
                ),
                'fab' => array(
                    'icono'  => 'add',
                    'label'  => 'Ofrecer regalo',
                    'accion' => 'create',
                ),
            ),

            'justicia_restaurativa' => array(
                'id'       => 'justicia_restaurativa',
                'titulo'   => 'Justicia Restaurativa',
                'icono'    => 'balance',
                'endpoint' => '/flavor/v1/justicia-restaurativa',
                'layout'   => 'list',
                'campos'   => array(
                    'titulo'    => 'titulo',
                    'subtitulo' => 'tipo',
                    'estado'    => 'estado',
                ),
            ),

            'huella_ecologica' => array(
                'id'       => 'huella_ecologica',
                'titulo'   => 'Huella Ecológica',
                'icono'    => 'eco',
                'endpoint' => '/flavor/v1/huella-ecologica',
                'layout'   => 'dashboard',
                'campos'   => array(
                    'titulo' => 'categoria',
                    'badge'  => 'valor',
                ),
                'acciones' => array(
                    array(
                        'id'    => 'calcular',
                        'label' => 'Calcular mi huella',
                        'icono' => 'calculate',
                        'tipo'  => 'navigate',
                        'route' => '/huella-ecologica/calculadora',
                    ),
                ),
            ),

            'economia_suficiencia' => array(
                'id'       => 'economia_suficiencia',
                'titulo'   => 'Economía de Suficiencia',
                'icono'    => 'spa',
                'endpoint' => '/flavor/v1/economia-suficiencia',
                'layout'   => 'card',
                'campos'   => array(
                    'titulo'      => 'titulo',
                    'descripcion' => 'descripcion',
                    'badge'       => 'categoria',
                ),
            ),
            'energia_comunitaria' => array(
                'id'       => 'energia_comunitaria',
                'titulo'   => 'Energía Comunitaria',
                'icono'    => 'bolt',
                'endpoint' => '/flavor/v1/energia-comunitaria',
                'layout'   => 'card',
                'campos'   => array(
                    'titulo'      => 'nombre',
                    'descripcion' => 'descripcion',
                    'badge'       => 'tipo_instalacion_principal',
                ),
            ),

            'saberes_ancestrales' => array(
                'id'       => 'saberes_ancestrales',
                'titulo'   => 'Saberes Ancestrales',
                'icono'    => 'history_edu',
                'endpoint' => '/flavor/v1/saberes-ancestrales',
                'layout'   => 'card',
                'campos'   => array(
                    'titulo'      => 'titulo',
                    'descripcion' => 'descripcion',
                    'imagen'      => 'imagen',
                    'badge'       => 'categoria',
                ),
            ),

            'biodiversidad_local' => array(
                'id'       => 'biodiversidad_local',
                'titulo'   => 'Biodiversidad Local',
                'icono'    => 'park',
                'endpoint' => '/flavor/v1/biodiversidad-local',
                'layout'   => 'grid',
                'campos'   => array(
                    'titulo'  => 'nombre',
                    'imagen'  => 'imagen',
                    'badge'   => 'tipo',
                ),
                'filtros' => array(
                    array(
                        'id'       => 'tipo',
                        'label'    => 'Tipo',
                        'tipo'     => 'select',
                        'opciones' => array(
                            array('value' => '',        'label' => 'Todos'),
                            array('value' => 'flora',   'label' => 'Flora'),
                            array('value' => 'fauna',   'label' => 'Fauna'),
                            array('value' => 'hongos',  'label' => 'Hongos'),
                        ),
                    ),
                ),
            ),

            'trabajo_digno' => array(
                'id'       => 'trabajo_digno',
                'titulo'   => 'Trabajo Digno',
                'icono'    => 'work_outline',
                'endpoint' => '/flavor/v1/trabajo-digno',
                'layout'   => 'card',
                'campos'   => array(
                    'titulo'      => 'titulo',
                    'descripcion' => 'descripcion',
                    'badge'       => 'tipo',
                    'estado'      => 'estado',
                ),
                'filtros' => array(
                    array(
                        'id'       => 'tipo',
                        'label'    => 'Tipo',
                        'tipo'     => 'select',
                        'opciones' => array(
                            array('value' => '',          'label' => 'Todos'),
                            array('value' => 'oferta',    'label' => 'Ofertas'),
                            array('value' => 'formacion', 'label' => 'Formación'),
                            array('value' => 'recurso',   'label' => 'Recursos'),
                        ),
                    ),
                ),
            ),
        );

        // Permitir a otros plugins añadir/modificar configuraciones
        $this->modules_config = apply_filters('flavor_mobile_modules_config', $this->modules_config);
    }

    /**
     * Devuelve todas las configuraciones de módulos
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_all_configs($request) {
        $include_all = $request->get_param('all');
        $configs = array();
        $active_count = 0;

        foreach ($this->modules_config as $id => $config) {
            $is_active = $this->is_module_active($id);

            if ($include_all) {
                // Incluir todos con indicador de estado
                $config['activo'] = $is_active;
                $configs[$id] = $config;
                if ($is_active) {
                    $active_count++;
                }
            } elseif ($is_active) {
                // Solo incluir módulos activos
                $configs[$id] = $config;
                $active_count++;
            }
        }

        return new WP_REST_Response(array(
            'success'      => true,
            'data'         => $configs,
            'count'        => count($configs),
            'active_count' => $active_count,
            'total'        => count($this->modules_config),
        ), 200);
    }

    /**
     * Devuelve la configuración de un módulo específico
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_module_config($request) {
        $module_id = $request->get_param('module_id');

        if (!isset($this->modules_config[$module_id])) {
            return new WP_REST_Response(array(
                'success' => false,
                'error'   => 'Módulo no encontrado',
            ), 404);
        }

        $config = $this->modules_config[$module_id];
        $config['activo'] = $this->is_module_active($module_id);

        return new WP_REST_Response(array(
            'success' => true,
            'data'    => $config,
        ), 200);
    }

    /**
     * Verifica si un módulo está activo
     *
     * @param string $module_id
     * @return bool
     */
    private function is_module_active($module_id) {
        // Obtener configuración del plugin
        $settings = get_option('flavor_chat_ia_settings', array());
        $active_modules = $settings['active_modules'] ?? array();

        // Si no hay lista de módulos activos, todos están activos por defecto
        if (empty($active_modules)) {
            return true;
        }

        // Verificar si el módulo está en la lista de activos
        return in_array($module_id, $active_modules) || isset($active_modules[$module_id]);
    }

    /**
     * Devuelve la documentación de un módulo específico
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_module_docs($request) {
        $module_id = $request->get_param('module_id');
        $docs = $this->get_modules_documentation();

        if (!isset($docs[$module_id])) {
            return new WP_REST_Response(array(
                'success' => false,
                'error'   => 'Documentación no encontrada para este módulo',
            ), 404);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data'    => $docs[$module_id],
        ), 200);
    }

    /**
     * Devuelve toda la documentación de módulos
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_all_docs($request) {
        $docs = $this->get_modules_documentation();

        return new WP_REST_Response(array(
            'success' => true,
            'data'    => $docs,
            'count'   => count($docs),
        ), 200);
    }

    /**
     * Documentación completa de todos los módulos
     *
     * @return array
     */
    private function get_modules_documentation() {
        return array(
            'eventos' => array(
                'id'          => 'eventos',
                'titulo'      => 'Eventos',
                'version'     => '1.0.0',
                'descripcion' => 'Sistema completo de gestión de eventos comunitarios. Permite crear, publicar y gestionar eventos con inscripciones, control de aforo, categorías y notificaciones automáticas a los participantes.',
                'caracteristicas' => array(
                    'Creación y edición de eventos con fechas, horarios y ubicación',
                    'Sistema de inscripciones con control de aforo',
                    'Categorías personalizables (cultural, deportivo, formativo...)',
                    'Galería de imágenes por evento',
                    'Notificaciones automáticas a inscritos',
                    'Calendario visual de eventos',
                    'Exportación de listados de asistentes',
                    'Eventos recurrentes (diario, semanal, mensual)',
                ),
                'casos_uso' => array(
                    'Asambleas vecinales',
                    'Talleres y cursos',
                    'Fiestas comunitarias',
                    'Reuniones de trabajo',
                    'Actividades deportivas',
                ),
                'modulos_relacionados' => array('espacios', 'reservas', 'notificaciones', 'participacion'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_eventos',
                'tablas' => array('wp_flavor_eventos', 'wp_flavor_eventos_inscripciones', 'wp_flavor_eventos_categorias'),
            ),

            'espacios_comunes' => array(
                'id'          => 'espacios_comunes',
                'titulo'      => 'Espacios Comunes',
                'version'     => '1.0.0',
                'descripcion' => 'Gestión integral de espacios compartidos de la comunidad. Permite administrar salas, pistas deportivas, jardines y otros recursos comunes con sistema de reservas, horarios y normas de uso.',
                'caracteristicas' => array(
                    'Catálogo de espacios con fotos y descripción',
                    'Capacidad y equipamiento de cada espacio',
                    'Horarios de disponibilidad configurables',
                    'Normas de uso personalizables',
                    'Integración con sistema de reservas',
                    'Bloqueo de fechas especiales',
                    'Historial de uso por espacio',
                    'Estadísticas de ocupación',
                ),
                'casos_uso' => array(
                    'Reserva de salón de actos',
                    'Pistas de pádel o tenis',
                    'Salas de reuniones',
                    'Zonas de barbacoa',
                    'Piscina comunitaria',
                ),
                'modulos_relacionados' => array('reservas', 'eventos', 'incidencias'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_espacios',
                'tablas' => array('wp_flavor_espacios', 'wp_flavor_espacios_horarios', 'wp_flavor_espacios_normas'),
            ),

            'reservas' => array(
                'id'          => 'reservas',
                'titulo'      => 'Sistema de Reservas',
                'version'     => '1.0.0',
                'descripcion' => 'Motor de reservas flexible para espacios, recursos y servicios. Gestiona la disponibilidad, conflictos de horarios, confirmaciones y cancelaciones con notificaciones automáticas.',
                'caracteristicas' => array(
                    'Calendario visual de disponibilidad',
                    'Detección automática de conflictos',
                    'Confirmación manual o automática',
                    'Límite de reservas por usuario',
                    'Reservas recurrentes',
                    'Lista de espera automática',
                    'Notificaciones por email y push',
                    'Penalizaciones por cancelación tardía',
                ),
                'casos_uso' => array(
                    'Reserva de espacios comunes',
                    'Citas para servicios',
                    'Turnos de uso de recursos',
                ),
                'modulos_relacionados' => array('espacios', 'bicicletas', 'parkings', 'recursos_compartidos'),
                'requisitos' => array('espacios'),
                'tabla_principal' => 'wp_flavor_reservas',
                'tablas' => array('wp_flavor_reservas', 'wp_flavor_reservas_recurrentes'),
            ),

            'incidencias' => array(
                'id'          => 'incidencias',
                'titulo'      => 'Gestión de Incidencias',
                'version'     => '1.0.0',
                'descripcion' => 'Sistema de reporte y seguimiento de incidencias urbanas o comunitarias. Los usuarios pueden reportar problemas con fotos y ubicación, y seguir el estado de resolución en tiempo real.',
                'caracteristicas' => array(
                    'Reporte con foto y geolocalización',
                    'Categorías configurables (averías, limpieza, seguridad...)',
                    'Estados de seguimiento (pendiente, en proceso, resuelto)',
                    'Asignación a responsables',
                    'Comentarios y actualizaciones',
                    'Mapa de incidencias activas',
                    'Estadísticas y tiempos de resolución',
                    'Notificaciones de cambio de estado',
                ),
                'casos_uso' => array(
                    'Averías en zonas comunes',
                    'Problemas de limpieza',
                    'Desperfectos urbanos',
                    'Incidencias de seguridad',
                    'Sugerencias de mejora',
                ),
                'modulos_relacionados' => array('espacios', 'transparencia', 'notificaciones'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_incidencias',
                'tablas' => array('wp_flavor_incidencias', 'wp_flavor_incidencias_comentarios', 'wp_flavor_incidencias_fotos'),
            ),

            'banco_tiempo' => array(
                'id'          => 'banco_tiempo',
                'titulo'      => 'Banco de Tiempo',
                'version'     => '1.0.0',
                'descripcion' => 'Sistema de intercambio de servicios basado en tiempo. Los miembros ofrecen y solicitan servicios valorados en horas, fomentando la economía colaborativa y el apoyo mutuo sin dinero de por medio.',
                'caracteristicas' => array(
                    'Catálogo de servicios ofrecidos',
                    'Sistema de saldo en horas',
                    'Solicitud y aceptación de intercambios',
                    'Valoraciones entre usuarios',
                    'Categorías de servicios',
                    'Historial de intercambios',
                    'Estadísticas de participación',
                    'Ranking de colaboradores',
                ),
                'casos_uso' => array(
                    'Clases particulares',
                    'Reparaciones domésticas',
                    'Cuidado de niños o mayores',
                    'Transporte y compras',
                    'Asesoramiento profesional',
                ),
                'modulos_relacionados' => array('ayuda_vecinal', 'economia_don', 'circulos_cuidados'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_banco_tiempo_servicios',
                'tablas' => array('wp_flavor_banco_tiempo_servicios', 'wp_flavor_banco_tiempo_intercambios', 'wp_flavor_banco_tiempo_saldo', 'wp_flavor_banco_tiempo_valoraciones'),
            ),

            'grupos_consumo' => array(
                'id'          => 'grupos_consumo',
                'titulo'      => 'Grupos de Consumo',
                'version'     => '1.0.0',
                'descripcion' => 'Plataforma para organizar compras colectivas directamente a productores locales. Gestiona pedidos, ciclos de compra, distribución y pagos, promoviendo el consumo responsable y de proximidad.',
                'caracteristicas' => array(
                    'Catálogo de productos por productor',
                    'Ciclos de pedido configurables',
                    'Cesta de compra compartida',
                    'Gestión de cuotas y pagos',
                    'Puntos de recogida',
                    'Histórico de pedidos',
                    'Comunicación con productores',
                    'Excedentes y trueques',
                ),
                'casos_uso' => array(
                    'Compra directa a agricultores',
                    'Productos ecológicos',
                    'Cestas de temporada',
                    'Compras mayoristas compartidas',
                ),
                'modulos_relacionados' => array('woocommerce', 'marketplace', 'economia_don'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_grupos_consumo',
                'tablas' => array('wp_flavor_grupos_consumo', 'wp_flavor_grupos_consumo_productos', 'wp_flavor_grupos_consumo_pedidos', 'wp_flavor_grupos_consumo_ciclos'),
            ),

            'marketplace' => array(
                'id'          => 'marketplace',
                'titulo'      => 'Marketplace Comunitario',
                'version'     => '1.0.0',
                'descripcion' => 'Tablón de anuncios para compraventa, regalo e intercambio entre miembros de la comunidad. Facilita la economía circular y el aprovechamiento de recursos dentro del vecindario.',
                'caracteristicas' => array(
                    'Anuncios de venta, regalo o búsqueda',
                    'Categorías y subcategorías',
                    'Fotos múltiples por anuncio',
                    'Chat entre usuarios',
                    'Búsqueda y filtros avanzados',
                    'Destacar anuncios',
                    'Gestión de favoritos',
                    'Valoraciones de vendedores',
                ),
                'casos_uso' => array(
                    'Venta de segunda mano',
                    'Regalo de objetos',
                    'Búsqueda de artículos',
                    'Intercambios',
                ),
                'modulos_relacionados' => array('economia_don', 'grupos_consumo', 'chat_interno'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_marketplace',
                'tablas' => array('wp_flavor_marketplace', 'wp_flavor_marketplace_categorias', 'wp_flavor_marketplace_favoritos'),
            ),

            'biblioteca' => array(
                'id'          => 'biblioteca',
                'titulo'      => 'Biblioteca Comunitaria',
                'version'     => '1.0.0',
                'descripcion' => 'Sistema de préstamo de libros, revistas y otros materiales entre vecinos. Incluye catálogo digital, reservas, control de préstamos y notificaciones de devolución.',
                'caracteristicas' => array(
                    'Catálogo con portadas y descripciones',
                    'Sistema de préstamo y devolución',
                    'Reservas de libros prestados',
                    'Recordatorios de devolución',
                    'Donación de libros',
                    'Valoraciones y reseñas',
                    'Búsqueda por título, autor o ISBN',
                    'Estadísticas de lectura',
                ),
                'casos_uso' => array(
                    'Préstamo de libros entre vecinos',
                    'Intercambio de revistas',
                    'Club de lectura',
                    'Biblioteca infantil',
                ),
                'modulos_relacionados' => array('recursos_compartidos', 'eventos'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_biblioteca',
                'tablas' => array('wp_flavor_biblioteca', 'wp_flavor_biblioteca_prestamos', 'wp_flavor_biblioteca_reservas'),
            ),

            'huertos_urbanos' => array(
                'id'          => 'huertos_urbanos',
                'titulo'      => 'Huertos Urbanos',
                'version'     => '1.0.0',
                'descripcion' => 'Gestión de parcelas de huerto comunitario. Administra la asignación de parcelas, turnos de riego, herramientas compartidas y actividades formativas sobre agricultura urbana.',
                'caracteristicas' => array(
                    'Mapa de parcelas con estado',
                    'Solicitud y asignación de parcelas',
                    'Lista de espera automática',
                    'Calendario de turnos de riego',
                    'Inventario de herramientas',
                    'Normas de uso del huerto',
                    'Actividades y talleres',
                    'Banco de semillas',
                ),
                'casos_uso' => array(
                    'Asignación de parcelas',
                    'Organización de turnos',
                    'Préstamo de herramientas',
                    'Talleres de cultivo',
                ),
                'modulos_relacionados' => array('compostaje', 'biodiversidad_local', 'grupos_consumo', 'recursos_compartidos'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_huertos',
                'tablas' => array('wp_flavor_huertos', 'wp_flavor_huertos_parcelas', 'wp_flavor_huertos_asignaciones'),
            ),

            'compostaje' => array(
                'id'          => 'compostaje',
                'titulo'      => 'Compostaje Comunitario',
                'version'     => '1.0.0',
                'descripcion' => 'Control y seguimiento de composteras comunitarias. Registra aportes de materia orgánica, turnos de volteo, estado del compost y distribución del abono resultante.',
                'caracteristicas' => array(
                    'Registro de aportes individuales',
                    'Calendario de turnos de volteo',
                    'Monitorización de temperatura y humedad',
                    'Estado de cada compostera',
                    'Distribución de compost maduro',
                    'Estadísticas de reducción de residuos',
                    'Gamificación con puntos verdes',
                    'Guías de compostaje',
                ),
                'casos_uso' => array(
                    'Reciclaje de residuos orgánicos',
                    'Producción de abono natural',
                    'Reducción de huella ecológica',
                ),
                'modulos_relacionados' => array('huertos', 'reciclaje', 'huella_ecologica'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_compostaje',
                'tablas' => array('wp_flavor_compostaje', 'wp_flavor_compostaje_aportes', 'wp_flavor_compostaje_turnos'),
            ),

            'reciclaje' => array(
                'id'          => 'reciclaje',
                'titulo'      => 'Gestión de Reciclaje',
                'version'     => '1.0.0',
                'descripcion' => 'Sistema de seguimiento y gamificación del reciclaje. Los usuarios registran su reciclaje, acumulan puntos y contribuyen a objetivos comunitarios de sostenibilidad.',
                'caracteristicas' => array(
                    'Registro de reciclaje por tipo',
                    'Sistema de puntos y recompensas',
                    'Objetivos comunitarios',
                    'Estadísticas de impacto',
                    'Información de puntos limpios',
                    'Guía de separación de residuos',
                    'Ranking de recicladores',
                    'Retos mensuales',
                ),
                'casos_uso' => array(
                    'Fomento del reciclaje',
                    'Educación ambiental',
                    'Competiciones entre comunidades',
                ),
                'modulos_relacionados' => array('compostaje', 'huella_ecologica', 'sello_conciencia'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_reciclaje',
                'tablas' => array('wp_flavor_reciclaje', 'wp_flavor_reciclaje_registros', 'wp_flavor_reciclaje_puntos'),
            ),

            'carpooling' => array(
                'id'          => 'carpooling',
                'titulo'      => 'Coche Compartido',
                'version'     => '1.0.0',
                'descripcion' => 'Plataforma para compartir viajes en coche entre miembros de la comunidad. Reduce costes, emisiones y aparcamiento facilitando la movilidad sostenible.',
                'caracteristicas' => array(
                    'Publicación de viajes',
                    'Búsqueda por origen/destino/fecha',
                    'Reserva de plazas',
                    'Chat entre conductor y pasajeros',
                    'Valoraciones mutuas',
                    'Viajes recurrentes',
                    'Cálculo de ahorro de CO2',
                    'Gestión de gastos compartidos',
                ),
                'casos_uso' => array(
                    'Viajes al trabajo',
                    'Excursiones de fin de semana',
                    'Desplazamientos escolares',
                    'Viajes a eventos',
                ),
                'modulos_relacionados' => array('bicicletas', 'huella_ecologica', 'chat_interno'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_carpooling',
                'tablas' => array('wp_flavor_carpooling', 'wp_flavor_carpooling_reservas', 'wp_flavor_carpooling_valoraciones'),
            ),

            'bicicletas_compartidas' => array(
                'id'          => 'bicicletas_compartidas',
                'titulo'      => 'Bicicletas Compartidas',
                'version'     => '1.0.0',
                'descripcion' => 'Sistema de préstamo de bicicletas comunitarias. Gestiona la flota, estaciones, reservas y mantenimiento promoviendo la movilidad sostenible en el barrio.',
                'caracteristicas' => array(
                    'Mapa de estaciones y disponibilidad',
                    'Reserva y desbloqueo de bicis',
                    'Control de tiempo de uso',
                    'Reporte de averías',
                    'Historial de préstamos',
                    'Estadísticas de uso',
                    'Gamificación (km recorridos)',
                    'Integración con cerraduras IoT',
                ),
                'casos_uso' => array(
                    'Desplazamientos cortos',
                    'Última milla',
                    'Paseos recreativos',
                    'Reducción de uso de coche',
                ),
                'modulos_relacionados' => array('carpooling', 'huella_ecologica', 'reservas'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_bicicletas',
                'tablas' => array('wp_flavor_bicicletas', 'wp_flavor_bicicletas_estaciones', 'wp_flavor_bicicletas_alquileres'),
            ),

            'parkings' => array(
                'id'          => 'parkings',
                'titulo'      => 'Gestión de Parkings',
                'version'     => '1.0.0',
                'descripcion' => 'Control de plazas de aparcamiento comunitarias. Administra la asignación, rotación, disponibilidad y reserva de plazas de garaje o parking exterior.',
                'caracteristicas' => array(
                    'Mapa de plazas con estado',
                    'Asignación fija o rotativa',
                    'Reserva temporal de plazas',
                    'Cesión entre vecinos',
                    'Control de acceso',
                    'Plazas para visitantes',
                    'Estadísticas de ocupación',
                    'Notificaciones de disponibilidad',
                ),
                'casos_uso' => array(
                    'Gestión de garaje comunitario',
                    'Plazas de visitantes',
                    'Rotación de plazas',
                    'Cesión temporal',
                ),
                'modulos_relacionados' => array('reservas', 'espacios'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_parkings',
                'tablas' => array('wp_flavor_parkings', 'wp_flavor_parkings_plazas', 'wp_flavor_parkings_reservas'),
            ),

            'ayuda_vecinal' => array(
                'id'          => 'ayuda_vecinal',
                'titulo'      => 'Red de Ayuda Vecinal',
                'version'     => '1.0.0',
                'descripcion' => 'Plataforma de ayuda mutua entre vecinos. Permite ofrecer o solicitar ayuda para tareas cotidianas, creando una red de apoyo solidario en la comunidad.',
                'caracteristicas' => array(
                    'Publicar ofertas o peticiones de ayuda',
                    'Categorías (compras, transporte, cuidados...)',
                    'Matching automático',
                    'Chat privado entre usuarios',
                    'Valoraciones de ayuda',
                    'Alertas de nuevas peticiones',
                    'Voluntarios verificados',
                    'Estadísticas de solidaridad',
                ),
                'casos_uso' => array(
                    'Compras para personas mayores',
                    'Acompañamiento a citas médicas',
                    'Pequeñas reparaciones',
                    'Cuidado de mascotas',
                    'Recogida de paquetes',
                ),
                'modulos_relacionados' => array('banco_tiempo', 'circulos_cuidados', 'chat_interno'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_ayuda_vecinal',
                'tablas' => array('wp_flavor_ayuda_vecinal', 'wp_flavor_ayuda_vecinal_respuestas'),
            ),

            'recursos_compartidos' => array(
                'id'          => 'recursos_compartidos',
                'titulo'      => 'Recursos Compartidos',
                'version'     => '1.0.0',
                'descripcion' => 'Sistema de préstamo de herramientas, equipos y otros recursos entre vecinos. Optimiza el uso de objetos que no se utilizan a diario compartiendo con la comunidad.',
                'caracteristicas' => array(
                    'Catálogo de recursos disponibles',
                    'Sistema de préstamo con plazos',
                    'Reservas anticipadas',
                    'Condiciones de uso',
                    'Depósito o fianza opcional',
                    'Valoraciones de préstamos',
                    'Recordatorios de devolución',
                    'Historial de uso por objeto',
                ),
                'casos_uso' => array(
                    'Préstamo de taladro o herramientas',
                    'Equipos de camping',
                    'Artículos de bebé',
                    'Electrodomésticos ocasionales',
                ),
                'modulos_relacionados' => array('biblioteca', 'banco_tiempo', 'economia_don'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_recursos_compartidos',
                'tablas' => array('wp_flavor_recursos_compartidos', 'wp_flavor_recursos_compartidos_prestamos'),
            ),

            'foros' => array(
                'id'          => 'foros',
                'titulo'      => 'Foros de Discusión',
                'version'     => '1.0.0',
                'descripcion' => 'Espacio de debate y conversación organizado por temas. Permite crear hilos de discusión, responder, citar y moderar contenido de forma comunitaria.',
                'caracteristicas' => array(
                    'Categorías y subcategorías',
                    'Hilos de discusión',
                    'Respuestas anidadas',
                    'Citas y menciones',
                    'Moderación por roles',
                    'Búsqueda en contenido',
                    'Notificaciones de respuestas',
                    'Temas fijados y destacados',
                ),
                'casos_uso' => array(
                    'Debates vecinales',
                    'Consultas técnicas',
                    'Propuestas de mejora',
                    'Información compartida',
                ),
                'modulos_relacionados' => array('red_social', 'participacion', 'chat_grupos'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_foros',
                'tablas' => array('wp_flavor_foros', 'wp_flavor_foros_temas', 'wp_flavor_foros_respuestas'),
            ),

            'chat_grupos' => array(
                'id'          => 'chat_grupos',
                'titulo'      => 'Chat de Grupos',
                'version'     => '1.0.0',
                'descripcion' => 'Sistema de chat en tiempo real para grupos temáticos. Permite crear canales de conversación por intereses, comisiones de trabajo o cualquier agrupación.',
                'caracteristicas' => array(
                    'Canales públicos y privados',
                    'Mensajes en tiempo real',
                    'Compartir archivos e imágenes',
                    'Menciones a usuarios',
                    'Búsqueda de mensajes',
                    'Administración de miembros',
                    'Notificaciones configurables',
                    'Historial persistente',
                ),
                'casos_uso' => array(
                    'Comisiones de trabajo',
                    'Grupos de interés',
                    'Coordinación de actividades',
                    'Comunicación de urgencia',
                ),
                'modulos_relacionados' => array('chat_interno', 'comunidades', 'foros'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_chat_grupos',
                'tablas' => array('wp_flavor_chat_grupos', 'wp_flavor_chat_grupos_miembros', 'wp_flavor_mensajes'),
            ),

            'chat_interno' => array(
                'id'          => 'chat_interno',
                'titulo'      => 'Mensajería Privada',
                'version'     => '1.0.0',
                'descripcion' => 'Sistema de mensajes privados entre usuarios. Comunicación directa y confidencial para coordinar ayuda, intercambios o cualquier tema personal.',
                'caracteristicas' => array(
                    'Conversaciones uno a uno',
                    'Estado de lectura',
                    'Compartir archivos',
                    'Búsqueda de conversaciones',
                    'Bloqueo de usuarios',
                    'Notificaciones push',
                    'Bandeja de entrada organizada',
                    'Cifrado de mensajes',
                ),
                'casos_uso' => array(
                    'Coordinación de intercambios',
                    'Consultas privadas',
                    'Negociación de precios',
                    'Mensajes de seguimiento',
                ),
                'modulos_relacionados' => array('chat_grupos', 'marketplace', 'ayuda_vecinal'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_conversaciones',
                'tablas' => array('wp_flavor_conversaciones', 'wp_flavor_mensajes_privados'),
            ),

            'comunidades' => array(
                'id'          => 'comunidades',
                'titulo'      => 'Gestión de Comunidades',
                'version'     => '1.0.0',
                'descripcion' => 'Estructura multisite para gestionar múltiples comunidades desde una instalación. Cada comunidad tiene sus propios miembros, configuración y módulos activos.',
                'caracteristicas' => array(
                    'Múltiples comunidades',
                    'Administradores por comunidad',
                    'Configuración independiente',
                    'Módulos activos por comunidad',
                    'Personalización visual',
                    'Miembros compartidos o exclusivos',
                    'Dashboard por comunidad',
                    'Federación entre comunidades',
                ),
                'casos_uso' => array(
                    'Red de comunidades de vecinos',
                    'Múltiples urbanizaciones',
                    'Cooperativas federadas',
                    'Municipios con varios barrios',
                ),
                'modulos_relacionados' => array('colectivos', 'socios'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_comunidades',
                'tablas' => array('wp_flavor_comunidades', 'wp_flavor_comunidades_miembros', 'wp_flavor_comunidades_config'),
            ),

            'colectivos' => array(
                'id'          => 'colectivos',
                'titulo'      => 'Colectivos y Asociaciones',
                'version'     => '1.0.0',
                'descripcion' => 'Directorio y gestión de colectivos, asociaciones y cooperativas del territorio. Facilita la visibilidad y coordinación entre organizaciones sociales.',
                'caracteristicas' => array(
                    'Directorio de organizaciones',
                    'Perfiles con actividad y contacto',
                    'Categorías (asociación, cooperativa...)',
                    'Eventos y actividades por colectivo',
                    'Miembros y roles',
                    'Documentos compartidos',
                    'Noticias y comunicados',
                    'Redes y alianzas',
                ),
                'casos_uso' => array(
                    'Mapa de tejido asociativo',
                    'Coordinación entre entidades',
                    'Agenda compartida',
                    'Recursos compartidos',
                ),
                'modulos_relacionados' => array('comunidades', 'socios', 'eventos', 'transparencia'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_colectivos',
                'tablas' => array('wp_flavor_colectivos', 'wp_flavor_colectivos_miembros'),
            ),

            'socios' => array(
                'id'          => 'socios',
                'titulo'      => 'Gestión de Socios',
                'version'     => '1.0.0',
                'descripcion' => 'Sistema completo de gestión de membresía. Controla altas, bajas, cuotas, carnets y comunicaciones con los socios de la organización.',
                'caracteristicas' => array(
                    'Registro y ficha de socios',
                    'Números de socio automáticos',
                    'Control de cuotas y pagos',
                    'Estados (activo, baja, moroso)',
                    'Carnets digitales',
                    'Comunicaciones masivas',
                    'Histórico de pagos',
                    'Exportación de datos',
                ),
                'casos_uso' => array(
                    'Asociaciones de vecinos',
                    'Clubs deportivos',
                    'Cooperativas',
                    'Entidades culturales',
                ),
                'modulos_relacionados' => array('colectivos', 'facturas', 'woocommerce'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_socios',
                'tablas' => array('wp_flavor_socios', 'wp_flavor_socios_cuotas', 'wp_flavor_socios_pagos'),
            ),

            'participacion' => array(
                'id'          => 'participacion',
                'titulo'      => 'Participación Ciudadana',
                'version'     => '1.0.0',
                'descripcion' => 'Herramientas de democracia participativa. Permite crear consultas, votaciones, encuestas y procesos participativos con diferentes metodologías.',
                'caracteristicas' => array(
                    'Consultas y referéndums',
                    'Votaciones vinculantes o consultivas',
                    'Múltiples sistemas de voto',
                    'Propuestas ciudadanas',
                    'Recogida de firmas',
                    'Debates previos',
                    'Resultados transparentes',
                    'Verificación de identidad',
                ),
                'casos_uso' => array(
                    'Decisiones comunitarias',
                    'Elección de representantes',
                    'Consultas de opinión',
                    'Priorización de propuestas',
                ),
                'modulos_relacionados' => array('presupuestos', 'foros', 'transparencia'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_participacion_procesos',
                'tablas' => array('wp_flavor_participacion_procesos', 'wp_flavor_participacion_propuestas', 'wp_flavor_participacion_votos'),
            ),

            'presupuestos_participativos' => array(
                'id'          => 'presupuestos_participativos',
                'titulo'      => 'Presupuestos Participativos',
                'version'     => '1.0.0',
                'descripcion' => 'Sistema para decidir colectivamente el destino de fondos comunitarios. Los miembros proponen proyectos, debaten y votan cómo invertir el presupuesto común.',
                'caracteristicas' => array(
                    'Fases configurables del proceso',
                    'Propuesta de proyectos',
                    'Valoración técnica',
                    'Votación por prioridades',
                    'Seguimiento de ejecución',
                    'Presupuesto por categorías',
                    'Histórico de ediciones',
                    'Informes de resultados',
                ),
                'casos_uso' => array(
                    'Inversiones comunitarias',
                    'Mejoras en instalaciones',
                    'Actividades culturales',
                    'Proyectos sociales',
                ),
                'modulos_relacionados' => array('participacion', 'transparencia', 'eventos'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_presupuestos',
                'tablas' => array('wp_flavor_presupuestos', 'wp_flavor_presupuestos_propuestas', 'wp_flavor_presupuestos_votos'),
            ),

            'transparencia' => array(
                'id'          => 'transparencia',
                'titulo'      => 'Portal de Transparencia',
                'version'     => '1.0.0',
                'descripcion' => 'Publicación de información institucional, económica y de gestión. Cumple con los requisitos de transparencia y rendición de cuentas ante la comunidad.',
                'caracteristicas' => array(
                    'Categorías de información',
                    'Publicación de documentos',
                    'Presupuestos y cuentas',
                    'Actas de reuniones',
                    'Contratos y convenios',
                    'Indicadores de gestión',
                    'Búsqueda de documentos',
                    'Suscripción a novedades',
                ),
                'casos_uso' => array(
                    'Publicación de cuentas',
                    'Actas de asambleas',
                    'Normativa interna',
                    'Informes de gestión',
                ),
                'modulos_relacionados' => array('participacion', 'presupuestos', 'colectivos'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_transparencia',
                'tablas' => array('wp_flavor_transparencia', 'wp_flavor_transparencia_documentos'),
            ),

            'avisos_municipales' => array(
                'id'          => 'avisos_municipales',
                'titulo'      => 'Avisos y Comunicados',
                'version'     => '1.0.0',
                'descripcion' => 'Sistema de comunicación oficial de la comunidad. Publica avisos importantes, alertas y comunicados con diferentes niveles de prioridad y canales de difusión.',
                'caracteristicas' => array(
                    'Avisos con prioridad',
                    'Fecha de caducidad',
                    'Categorías (obras, seguridad, info...)',
                    'Notificaciones push',
                    'Confirmación de lectura',
                    'Adjuntos y enlaces',
                    'Programación de publicación',
                    'Historial de avisos',
                ),
                'casos_uso' => array(
                    'Cortes de suministros',
                    'Avisos de obras',
                    'Convocatorias',
                    'Alertas de seguridad',
                ),
                'modulos_relacionados' => array('notificaciones', 'transparencia'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_avisos',
                'tablas' => array('wp_flavor_avisos', 'wp_flavor_avisos_lecturas'),
            ),

            'tramites' => array(
                'id'          => 'tramites',
                'titulo'      => 'Gestión de Trámites',
                'version'     => '1.0.0',
                'descripcion' => 'Oficina virtual para realizar gestiones administrativas. Los usuarios solicitan trámites online y siguen su estado hasta la resolución.',
                'caracteristicas' => array(
                    'Catálogo de trámites disponibles',
                    'Formularios dinámicos',
                    'Adjuntar documentación',
                    'Estados de tramitación',
                    'Notificaciones de avance',
                    'Asignación a gestores',
                    'Plazos y SLAs',
                    'Histórico de solicitudes',
                ),
                'casos_uso' => array(
                    'Solicitud de certificados',
                    'Autorizaciones de obra',
                    'Cambios de titularidad',
                    'Reclamaciones',
                ),
                'modulos_relacionados' => array('facturas', 'socios', 'notificaciones'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_tramites',
                'tablas' => array('wp_flavor_tramites', 'wp_flavor_tramites_tipos', 'wp_flavor_tramites_documentos'),
            ),

            'cursos' => array(
                'id'          => 'cursos',
                'titulo'      => 'Plataforma de Cursos',
                'version'     => '1.0.0',
                'descripcion' => 'Sistema de formación online con cursos estructurados en lecciones y módulos. Incluye contenido multimedia, evaluaciones y certificados de finalización.',
                'caracteristicas' => array(
                    'Cursos con lecciones y módulos',
                    'Vídeos, textos y documentos',
                    'Evaluaciones y tests',
                    'Progreso del alumno',
                    'Certificados automáticos',
                    'Foros por curso',
                    'Inscripciones y plazas',
                    'Cursos de pago opcionales',
                ),
                'casos_uso' => array(
                    'Formación interna',
                    'Cursos de idiomas',
                    'Talleres online',
                    'Onboarding de nuevos miembros',
                ),
                'modulos_relacionados' => array('talleres', 'eventos', 'woocommerce'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_cursos',
                'tablas' => array('wp_flavor_cursos', 'wp_flavor_cursos_lecciones', 'wp_flavor_cursos_inscripciones', 'wp_flavor_cursos_progreso'),
            ),

            'talleres' => array(
                'id'          => 'talleres',
                'titulo'      => 'Talleres y Workshops',
                'version'     => '1.0.0',
                'descripcion' => 'Gestión de talleres presenciales o híbridos. Programa actividades formativas puntuales con inscripción, materiales y seguimiento de asistencia.',
                'caracteristicas' => array(
                    'Programación de talleres',
                    'Inscripciones con plazas',
                    'Lista de espera',
                    'Material descargable',
                    'Control de asistencia',
                    'Certificados de asistencia',
                    'Valoraciones',
                    'Talleres recurrentes',
                ),
                'casos_uso' => array(
                    'Talleres de manualidades',
                    'Clases de cocina',
                    'Workshops técnicos',
                    'Actividades infantiles',
                ),
                'modulos_relacionados' => array('cursos', 'eventos', 'espacios'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_talleres',
                'tablas' => array('wp_flavor_talleres', 'wp_flavor_talleres_inscripciones'),
            ),

            'podcast' => array(
                'id'          => 'podcast',
                'titulo'      => 'Podcast Comunitario',
                'version'     => '1.0.0',
                'descripcion' => 'Plataforma de podcast para la comunidad. Publica episodios de audio con feeds RSS, reproductor integrado y estadísticas de escucha.',
                'caracteristicas' => array(
                    'Publicación de episodios',
                    'Múltiples programas',
                    'Reproductor integrado',
                    'Feed RSS automático',
                    'Estadísticas de escucha',
                    'Transcripciones',
                    'Comentarios por episodio',
                    'Suscripción a programas',
                ),
                'casos_uso' => array(
                    'Programa informativo',
                    'Entrevistas vecinales',
                    'Debates y tertulias',
                    'Contenido educativo',
                ),
                'modulos_relacionados' => array('radio', 'multimedia', 'eventos'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_podcast_episodios',
                'tablas' => array('wp_flavor_podcast_programas', 'wp_flavor_podcast_episodios'),
            ),

            'radio' => array(
                'id'          => 'radio',
                'titulo'      => 'Radio Comunitaria',
                'version'     => '1.0.0',
                'descripcion' => 'Emisora de radio online con programación, streaming en directo, dedicatorias y participación de los oyentes en tiempo real.',
                'caracteristicas' => array(
                    'Streaming en directo',
                    'Parrilla de programación',
                    'Programas grabados',
                    'Dedicatorias de oyentes',
                    'Chat de oyentes',
                    'Propuestas musicales',
                    'Estadísticas de audiencia',
                    'Archivo de emisiones',
                ),
                'casos_uso' => array(
                    'Radio barrial',
                    'Emisora escolar',
                    'Radio asociativa',
                    'Eventos en directo',
                ),
                'modulos_relacionados' => array('podcast', 'multimedia', 'chat_grupos'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_radio_programas',
                'tablas' => array('wp_flavor_radio_programas', 'wp_flavor_radio_programacion', 'wp_flavor_radio_dedicatorias', 'wp_flavor_radio_oyentes'),
            ),

            'multimedia' => array(
                'id'          => 'multimedia',
                'titulo'      => 'Galería Multimedia',
                'version'     => '1.0.0',
                'descripcion' => 'Biblioteca de contenido multimedia de la comunidad. Organiza fotos, vídeos y documentos en álbumes y categorías accesibles para todos.',
                'caracteristicas' => array(
                    'Galería de fotos',
                    'Videoteca',
                    'Álbumes y categorías',
                    'Subida colaborativa',
                    'Etiquetado de personas',
                    'Comentarios y likes',
                    'Descarga de originales',
                    'Slideshow automático',
                ),
                'casos_uso' => array(
                    'Fotos de eventos',
                    'Vídeos institucionales',
                    'Memorias gráficas',
                    'Archivo histórico',
                ),
                'modulos_relacionados' => array('eventos', 'podcast', 'red_social'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_multimedia',
                'tablas' => array('wp_flavor_multimedia', 'wp_flavor_multimedia_albumes'),
            ),

            'red_social' => array(
                'id'          => 'red_social',
                'titulo'      => 'Red Social Comunitaria',
                'version'     => '1.0.0',
                'descripcion' => 'Red social privada para la comunidad. Un espacio tipo Facebook donde los miembros comparten publicaciones, fotos, comentarios y reacciones.',
                'caracteristicas' => array(
                    'Publicaciones con fotos',
                    'Comentarios y reacciones',
                    'Perfiles de usuario',
                    'Seguimiento entre usuarios',
                    'Hashtags y menciones',
                    'Noticias en el muro',
                    'Grupos de interés',
                    'Notificaciones de actividad',
                ),
                'casos_uso' => array(
                    'Comunicación informal',
                    'Compartir momentos',
                    'Crear comunidad',
                    'Networking vecinal',
                ),
                'modulos_relacionados' => array('foros', 'chat_grupos', 'multimedia'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_red_social_posts',
                'tablas' => array('wp_flavor_red_social_posts', 'wp_flavor_red_social_comentarios', 'wp_flavor_red_social_likes', 'wp_flavor_red_social_seguidores'),
            ),

            'woocommerce' => array(
                'id'          => 'woocommerce',
                'titulo'      => 'Tienda Online',
                'version'     => '1.0.0',
                'descripcion' => 'Integración con WooCommerce para venta de productos y servicios. Accede al catálogo, carrito de compra y gestión de pedidos desde la app móvil.',
                'caracteristicas' => array(
                    'Catálogo de productos',
                    'Carrito de compra',
                    'Proceso de checkout',
                    'Historial de pedidos',
                    'Métodos de pago integrados',
                    'Cupones y descuentos',
                    'Notificaciones de pedido',
                    'Productos variables',
                ),
                'casos_uso' => array(
                    'Tienda de la comunidad',
                    'Venta de cuotas y entradas',
                    'Merchandising',
                    'Servicios de pago',
                ),
                'modulos_relacionados' => array('grupos_consumo', 'socios', 'facturas'),
                'requisitos' => array('WooCommerce plugin'),
                'tabla_principal' => 'wc_orders',
                'tablas' => array(),
            ),

            'facturas' => array(
                'id'          => 'facturas',
                'titulo'      => 'Gestión de Facturas',
                'version'     => '1.0.0',
                'descripcion' => 'Sistema de facturación para administradores. Genera, envía y gestiona facturas con numeración automática, PDF profesional y control de cobros.',
                'caracteristicas' => array(
                    'Generación de facturas',
                    'Numeración automática',
                    'PDF profesional',
                    'Envío por email',
                    'Control de estados',
                    'Recordatorios de pago',
                    'Informes y listados',
                    'Integración contable',
                ),
                'casos_uso' => array(
                    'Facturación de cuotas',
                    'Servicios prestados',
                    'Alquileres de espacios',
                    'Venta de productos',
                ),
                'modulos_relacionados' => array('socios', 'woocommerce', 'tramites'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_facturas',
                'tablas' => array('wp_flavor_facturas', 'wp_flavor_facturas_lineas'),
            ),

            'fichaje_empleados' => array(
                'id'          => 'fichaje_empleados',
                'titulo'      => 'Control de Fichaje',
                'version'     => '1.0.0',
                'descripcion' => 'Sistema de control horario para empleados o voluntarios. Registro de entradas y salidas, cálculo de horas y generación de informes de asistencia.',
                'caracteristicas' => array(
                    'Fichaje de entrada/salida',
                    'Geolocalización opcional',
                    'Cálculo de horas trabajadas',
                    'Pausas y descansos',
                    'Informes mensuales',
                    'Exportación de datos',
                    'Justificación de ausencias',
                    'Alertas de incidencias',
                ),
                'casos_uso' => array(
                    'Control de empleados',
                    'Registro de voluntarios',
                    'Cumplimiento normativo',
                    'Gestión de turnos',
                ),
                'modulos_relacionados' => array('socios', 'empresarial'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_fichajes',
                'tablas' => array('wp_flavor_fichajes'),
            ),

            'clientes' => array(
                'id'          => 'clientes',
                'titulo'      => 'CRM de Clientes',
                'version'     => '1.0.0',
                'descripcion' => 'Gestión básica de relaciones con clientes. Fichas de contacto, historial de interacciones, notas y seguimiento de oportunidades comerciales.',
                'caracteristicas' => array(
                    'Fichas de clientes',
                    'Datos de contacto',
                    'Historial de compras',
                    'Notas y comentarios',
                    'Etiquetas y segmentos',
                    'Búsqueda y filtros',
                    'Importación/exportación',
                    'Comunicaciones masivas',
                ),
                'casos_uso' => array(
                    'Gestión de contactos',
                    'Seguimiento comercial',
                    'Fidelización',
                    'Análisis de clientes',
                ),
                'modulos_relacionados' => array('facturas', 'woocommerce', 'email_marketing'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_clientes',
                'tablas' => array('wp_flavor_clientes', 'wp_flavor_clientes_notas'),
            ),

            'empresarial' => array(
                'id'          => 'empresarial',
                'titulo'      => 'Suite Empresarial',
                'version'     => '1.0.0',
                'descripcion' => 'Conjunto de herramientas profesionales para la gestión empresarial. Dashboard de métricas, KPIs, informes y componentes de administración avanzada.',
                'caracteristicas' => array(
                    'Dashboard ejecutivo',
                    'KPIs configurables',
                    'Informes personalizados',
                    'Gestión de proyectos básica',
                    'Control de gastos',
                    'Documentos corporativos',
                    'Calendario corporativo',
                    'Integraciones API',
                ),
                'casos_uso' => array(
                    'Gestión de cooperativa',
                    'Administración de empresa',
                    'Control de negocio',
                    'Reporting directivo',
                ),
                'modulos_relacionados' => array('clientes', 'facturas', 'fichaje'),
                'requisitos' => array(),
                'tabla_principal' => null,
                'tablas' => array(),
            ),

            'bares' => array(
                'id'          => 'bares',
                'titulo'      => 'Directorio de Hostelería',
                'version'     => '1.0.0',
                'descripcion' => 'Directorio de bares, restaurantes y locales de hostelería del barrio. Información de contacto, horarios, cartas y valoraciones de los vecinos.',
                'caracteristicas' => array(
                    'Fichas de establecimientos',
                    'Horarios y contacto',
                    'Cartas y menús',
                    'Fotos y galería',
                    'Valoraciones y reseñas',
                    'Geolocalización',
                    'Ofertas y promociones',
                    'Reservas online',
                ),
                'casos_uso' => array(
                    'Guía gastronómica local',
                    'Promoción de comercio local',
                    'Recomendaciones vecinales',
                ),
                'modulos_relacionados' => array('marketplace', 'eventos'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_bares',
                'tablas' => array('wp_flavor_bares', 'wp_flavor_bares_valoraciones'),
            ),

            'advertising' => array(
                'id'          => 'advertising',
                'titulo'      => 'Publicidad Ética',
                'version'     => '1.0.0',
                'descripcion' => 'Sistema de anuncios éticos para financiar la plataforma. Publicidad respetuosa, de comercios locales y proyectos afines a los valores de la comunidad.',
                'caracteristicas' => array(
                    'Banners y anuncios',
                    'Segmentación por sección',
                    'Control de impresiones',
                    'Estadísticas de clics',
                    'Criterios éticos',
                    'Gestión de anunciantes',
                    'Programación temporal',
                    'Facturación integrada',
                ),
                'casos_uso' => array(
                    'Financiación de la plataforma',
                    'Promoción de comercio local',
                    'Patrocinio de eventos',
                ),
                'modulos_relacionados' => array('bares', 'eventos', 'facturas'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_publicidad',
                'tablas' => array('wp_flavor_publicidad', 'wp_flavor_publicidad_impresiones'),
            ),

            'email_marketing' => array(
                'id'          => 'email_marketing',
                'titulo'      => 'Email Marketing',
                'version'     => '1.0.0',
                'descripcion' => 'Sistema de envío de newsletters y campañas de email. Segmentación de audiencias, plantillas profesionales y estadísticas de apertura y clics.',
                'caracteristicas' => array(
                    'Editor de newsletters',
                    'Plantillas responsive',
                    'Listas y segmentos',
                    'Programación de envíos',
                    'Estadísticas detalladas',
                    'A/B testing',
                    'Automatizaciones básicas',
                    'Gestión de suscriptores',
                ),
                'casos_uso' => array(
                    'Newsletter semanal',
                    'Comunicados importantes',
                    'Campañas promocionales',
                    'Recordatorios de eventos',
                ),
                'modulos_relacionados' => array('avisos', 'socios', 'clientes'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_email_campaigns',
                'tablas' => array('wp_flavor_email_campaigns', 'wp_flavor_email_suscriptores', 'wp_flavor_email_estadisticas'),
            ),

            'circulos_cuidados' => array(
                'id'          => 'circulos_cuidados',
                'titulo'      => 'Círculos de Cuidados',
                'version'     => '1.0.0',
                'descripcion' => 'Organización de redes de cuidados mutuos. Grupos de apoyo para situaciones de dependencia, enfermedad, maternidad u otras necesidades de cuidado.',
                'caracteristicas' => array(
                    'Creación de círculos',
                    'Coordinación de turnos',
                    'Calendario de cuidados',
                    'Chat del grupo',
                    'Diario de seguimiento',
                    'Recursos y guías',
                    'Avisos urgentes',
                    'Historial de intervenciones',
                ),
                'casos_uso' => array(
                    'Apoyo a personas mayores',
                    'Acompañamiento en enfermedad',
                    'Crianza compartida',
                    'Cuidado de dependientes',
                ),
                'modulos_relacionados' => array('ayuda_vecinal', 'banco_tiempo', 'chat_grupos'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_circulos_cuidados',
                'tablas' => array('wp_flavor_circulos_cuidados', 'wp_flavor_circulos_cuidados_miembros', 'wp_flavor_circulos_cuidados_turnos'),
            ),

            'economia_don' => array(
                'id'          => 'economia_don',
                'titulo'      => 'Economía del Don',
                'version'     => '1.0.0',
                'descripcion' => 'Sistema de regalo y donación sin expectativa de retorno. Promueve la generosidad y el compartir recursos más allá del intercambio comercial.',
                'caracteristicas' => array(
                    'Publicar regalos',
                    'Solicitar objetos',
                    'Sin contraprestación',
                    'Categorías de objetos',
                    'Contacto directo',
                    'Historial de donaciones',
                    'Agradecimientos públicos',
                    'Estadísticas de generosidad',
                ),
                'casos_uso' => array(
                    'Donar ropa y juguetes',
                    'Compartir excedentes',
                    'Muebles sin uso',
                    'Electrodomésticos',
                ),
                'modulos_relacionados' => array('marketplace', 'recursos_compartidos', 'ayuda_vecinal'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_economia_don',
                'tablas' => array('wp_flavor_economia_don'),
            ),

            'justicia_restaurativa' => array(
                'id'          => 'justicia_restaurativa',
                'titulo'      => 'Justicia Restaurativa',
                'version'     => '1.0.0',
                'descripcion' => 'Herramientas para la resolución de conflictos mediante el diálogo y la reparación. Procesos de mediación, círculos restaurativos y acuerdos de convivencia.',
                'caracteristicas' => array(
                    'Solicitud de mediación',
                    'Asignación de mediadores',
                    'Círculos de diálogo',
                    'Acuerdos de reparación',
                    'Seguimiento de compromisos',
                    'Confidencialidad garantizada',
                    'Formación de mediadores',
                    'Estadísticas anónimas',
                ),
                'casos_uso' => array(
                    'Conflictos vecinales',
                    'Disputas de convivencia',
                    'Reparación de daños',
                    'Reconciliación comunitaria',
                ),
                'modulos_relacionados' => array('incidencias', 'participacion', 'circulos_cuidados'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_justicia_restaurativa',
                'tablas' => array('wp_flavor_justicia_restaurativa', 'wp_flavor_justicia_restaurativa_procesos'),
            ),

            'huella_ecologica' => array(
                'id'          => 'huella_ecologica',
                'titulo'      => 'Calculadora de Huella Ecológica',
                'version'     => '1.0.0',
                'descripcion' => 'Herramienta para medir y reducir el impacto ambiental individual y colectivo. Calcula la huella de carbono, propone mejoras y gamifica la sostenibilidad.',
                'caracteristicas' => array(
                    'Calculadora de huella',
                    'Categorías (transporte, energía, consumo)',
                    'Comparativa con medias',
                    'Consejos personalizados',
                    'Retos de reducción',
                    'Seguimiento temporal',
                    'Huella comunitaria',
                    'Ranking de sostenibilidad',
                ),
                'casos_uso' => array(
                    'Concienciación ambiental',
                    'Reducción de emisiones',
                    'Gamificación verde',
                    'Objetivos comunitarios',
                ),
                'modulos_relacionados' => array('reciclaje', 'compostaje', 'carpooling', 'sello_conciencia'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_huella_ecologica',
                'tablas' => array('wp_flavor_huella_ecologica', 'wp_flavor_huella_ecologica_registros'),
            ),

            'economia_suficiencia' => array(
                'id'          => 'economia_suficiencia',
                'titulo'      => 'Economía de Suficiencia',
                'version'     => '1.0.0',
                'descripcion' => 'Recursos y prácticas para un estilo de vida sostenible basado en lo suficiente. Guías de consumo consciente, reparación, reutilización y autosuficiencia.',
                'caracteristicas' => array(
                    'Guías de autosuficiencia',
                    'Tutoriales de reparación',
                    'Recetas de productos caseros',
                    'Calculadora de ahorro',
                    'Retos mensuales',
                    'Comunidad de práctica',
                    'Recursos compartidos',
                    'Historias de éxito',
                ),
                'casos_uso' => array(
                    'Reducir consumo',
                    'DIY y reparación',
                    'Vida más simple',
                    'Ahorro consciente',
                ),
                'modulos_relacionados' => array('huella_ecologica', 'recursos_compartidos', 'saberes_ancestrales'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_economia_suficiencia',
                'tablas' => array('wp_flavor_economia_suficiencia'),
            ),
            'energia_comunitaria' => array(
                'id'          => 'energia_comunitaria',
                'titulo'      => 'Energía Comunitaria',
                'version'     => '1.0.0',
                'descripcion' => 'Gestión comunitaria de generación renovable, consumo, reparto, mantenimiento e inversiones para avanzar hacia la autosuficiencia energética local.',
                'caracteristicas' => array(
                    'Comunidades energéticas',
                    'Instalaciones y activos',
                    'Lecturas de producción y consumo',
                    'Reparto de excedentes',
                    'Incidencias y mantenimiento',
                    'Panel de autosuficiencia',
                    'Integración con comunidades y huella ecológica',
                ),
                'casos_uso' => array(
                    'Cooperativas solares',
                    'Autoconsumo compartido',
                    'Microredes vecinales',
                    'Compras colectivas de instalaciones',
                ),
                'modulos_relacionados' => array('comunidades', 'huella_ecologica', 'presupuestos_participativos', 'eventos', 'economia_suficiencia'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_energia_comunidades',
                'tablas' => array('wp_flavor_energia_comunidades', 'wp_flavor_energia_instalaciones', 'wp_flavor_energia_lecturas', 'wp_flavor_energia_incidencias'),
            ),

            'saberes_ancestrales' => array(
                'id'          => 'saberes_ancestrales',
                'titulo'      => 'Saberes Ancestrales',
                'version'     => '1.0.0',
                'descripcion' => 'Repositorio de conocimientos tradicionales y oficios antiguos. Preserva y transmite saberes que corren riesgo de perderse: artesanía, remedios naturales, técnicas agrícolas...',
                'caracteristicas' => array(
                    'Fichas de saberes',
                    'Categorías (oficios, remedios, cocina...)',
                    'Contenido multimedia',
                    'Contribución colaborativa',
                    'Personas portadoras de saber',
                    'Talleres de transmisión',
                    'Mapa de saberes locales',
                    'Archivo histórico',
                ),
                'casos_uso' => array(
                    'Preservar oficios',
                    'Recetas tradicionales',
                    'Medicina natural',
                    'Técnicas artesanales',
                ),
                'modulos_relacionados' => array('talleres', 'biodiversidad_local', 'economia_suficiencia'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_saberes_ancestrales',
                'tablas' => array('wp_flavor_saberes_ancestrales'),
            ),

            'biodiversidad_local' => array(
                'id'          => 'biodiversidad_local',
                'titulo'      => 'Biodiversidad Local',
                'version'     => '1.0.0',
                'descripcion' => 'Catálogo colaborativo de flora, fauna y ecosistemas del territorio. Ciencia ciudadana para conocer, proteger y disfrutar la naturaleza cercana.',
                'caracteristicas' => array(
                    'Catálogo de especies',
                    'Fichas con fotos e info',
                    'Avistamientos geolocalizados',
                    'Contribución ciudadana',
                    'Rutas de observación',
                    'Calendario fenológico',
                    'Especies amenazadas',
                    'Estadísticas de biodiversidad',
                ),
                'casos_uso' => array(
                    'Ciencia ciudadana',
                    'Educación ambiental',
                    'Protección de especies',
                    'Turismo de naturaleza',
                ),
                'modulos_relacionados' => array('huertos', 'huella_ecologica', 'saberes_ancestrales'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_biodiversidad',
                'tablas' => array('wp_flavor_biodiversidad', 'wp_flavor_biodiversidad_avistamientos'),
            ),

            'trabajo_digno' => array(
                'id'          => 'trabajo_digno',
                'titulo'      => 'Bolsa de Trabajo Digno',
                'version'     => '1.0.0',
                'descripcion' => 'Plataforma de empleo con criterios éticos. Ofertas de trabajo que garantizan condiciones dignas, formación profesional y recursos para el emprendimiento social.',
                'caracteristicas' => array(
                    'Ofertas de empleo éticas',
                    'Criterios de trabajo digno',
                    'Perfiles profesionales',
                    'Formación y capacitación',
                    'Recursos para emprendedores',
                    'Cooperativismo',
                    'Mentoría profesional',
                    'Networking laboral',
                ),
                'casos_uso' => array(
                    'Búsqueda de empleo',
                    'Ofertas con garantías',
                    'Emprendimiento social',
                    'Formación laboral',
                ),
                'modulos_relacionados' => array('cursos', 'colectivos', 'economia_suficiencia'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_trabajo_digno',
                'tablas' => array('wp_flavor_trabajo_digno', 'wp_flavor_trabajo_digno_ofertas'),
            ),

            'sello_conciencia' => array(
                'id'          => 'sello_conciencia',
                'titulo'      => 'Sello Conciencia',
                'version'     => '1.0.0',
                'descripcion' => 'Sistema de certificación ética para comercios y servicios. Acredita el cumplimiento de criterios sociales, ambientales y de comercio justo.',
                'caracteristicas' => array(
                    'Criterios de certificación',
                    'Proceso de evaluación',
                    'Sellos por categoría',
                    'Directorio de certificados',
                    'Renovación periódica',
                    'Distintivos visuales',
                    'Beneficios para certificados',
                    'Verificación pública',
                ),
                'casos_uso' => array(
                    'Certificar comercios éticos',
                    'Promover consumo responsable',
                    'Visibilizar buenas prácticas',
                    'Red de comercio justo',
                ),
                'modulos_relacionados' => array('bares', 'colectivos', 'huella_ecologica'),
                'requisitos' => array(),
                'tabla_principal' => 'wp_flavor_sello_conciencia',
                'tablas' => array('wp_flavor_sello_conciencia', 'wp_flavor_sello_conciencia_certificados'),
            ),

            'trading_ia' => array(
                'id'          => 'trading_ia',
                'titulo'      => 'Trading con IA',
                'version'     => '1.0.0',
                'descripcion' => 'Herramientas de trading asistido por inteligencia artificial. Análisis de mercados, señales automatizadas y gestión de portfolios con IA.',
                'caracteristicas' => array(
                    'Dashboard de mercados',
                    'Señales de trading',
                    'Análisis técnico automático',
                    'Gestión de portfolio',
                    'Backtesting de estrategias',
                    'Alertas personalizadas',
                    'Integración con exchanges',
                    'Informes de rendimiento',
                ),
                'casos_uso' => array(
                    'Trading automatizado',
                    'Análisis de criptomonedas',
                    'Gestión de inversiones',
                    'Educación financiera',
                ),
                'modulos_relacionados' => array('dex_solana'),
                'requisitos' => array(),
                'tabla_principal' => null,
                'tablas' => array(),
            ),

            'dex_solana' => array(
                'id'          => 'dex_solana',
                'titulo'      => 'DEX en Solana',
                'version'     => '1.0.0',
                'descripcion' => 'Intercambio descentralizado en la blockchain de Solana. Swap de tokens, pools de liquidez y operaciones DeFi con bajas comisiones.',
                'caracteristicas' => array(
                    'Swap de tokens',
                    'Conexión con Jupiter',
                    'Pools de liquidez',
                    'Historial de operaciones',
                    'Wallet integration',
                    'Slippage configurable',
                    'Favoritos de tokens',
                    'Gráficos de precios',
                ),
                'casos_uso' => array(
                    'Intercambio de tokens',
                    'DeFi en Solana',
                    'Provisión de liquidez',
                    'Arbitraje',
                ),
                'modulos_relacionados' => array('trading_ia'),
                'requisitos' => array(),
                'tabla_principal' => null,
                'tablas' => array(),
            ),

            'themacle' => array(
                'id'          => 'themacle',
                'titulo'      => 'Themacle',
                'version'     => '1.0.0',
                'descripcion' => 'Sistema de gestión de temas y estilos visuales. Permite personalizar la apariencia de la plataforma con plantillas y configuraciones predefinidas.',
                'caracteristicas' => array(
                    'Biblioteca de temas',
                    'Personalización de colores',
                    'Tipografías configurables',
                    'Layouts predefinidos',
                    'Preview en tiempo real',
                    'Exportar/importar temas',
                    'Temas por comunidad',
                    'Modo oscuro',
                ),
                'casos_uso' => array(
                    'Personalizar apariencia',
                    'Adaptar a marca',
                    'Temas estacionales',
                    'Accesibilidad visual',
                ),
                'modulos_relacionados' => array('comunidades'),
                'requisitos' => array(),
                'tabla_principal' => null,
                'tablas' => array(),
            ),
        );
    }
}

// Inicializar la API
new Flavor_Module_Config_API();

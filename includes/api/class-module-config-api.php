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

            'banco-tiempo' => array(
                'id'       => 'banco-tiempo',
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

            'grupos-consumo' => array(
                'id'       => 'grupos-consumo',
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

            'ayuda-vecinal' => array(
                'id'       => 'ayuda-vecinal',
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

            'recursos-compartidos' => array(
                'id'       => 'recursos-compartidos',
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

            'red-social' => array(
                'id'       => 'red-social',
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

            'chat-grupos' => array(
                'id'       => 'chat-grupos',
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

            'chat-interno' => array(
                'id'       => 'chat-interno',
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

            'trading-ia' => array(
                'id'       => 'trading-ia',
                'titulo'   => 'Trading IA',
                'icono'    => 'trending_up',
                'endpoint' => '/flavor/v1/trading-ia',
                'layout'   => 'dashboard',
                'campos'   => array(
                    'titulo' => 'par',
                    'badge'  => 'precio',
                ),
            ),

            'dex-solana' => array(
                'id'       => 'dex-solana',
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

            'email-marketing' => array(
                'id'       => 'email-marketing',
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

            'sello-conciencia' => array(
                'id'       => 'sello-conciencia',
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

            'circulos-cuidados' => array(
                'id'       => 'circulos-cuidados',
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

            'economia-don' => array(
                'id'       => 'economia-don',
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

            'justicia-restaurativa' => array(
                'id'       => 'justicia-restaurativa',
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

            'huella-ecologica' => array(
                'id'       => 'huella-ecologica',
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

            'economia-suficiencia' => array(
                'id'       => 'economia-suficiencia',
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

            'saberes-ancestrales' => array(
                'id'       => 'saberes-ancestrales',
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

            'biodiversidad-local' => array(
                'id'       => 'biodiversidad-local',
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

            'trabajo-digno' => array(
                'id'       => 'trabajo-digno',
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
        // Verificar contra opciones del plugin
        $active_modules = get_option('flavor_active_modules', array());

        // Si no hay lista de módulos activos, todos están activos por defecto
        if (empty($active_modules)) {
            return true;
        }

        return in_array($module_id, $active_modules);
    }
}

// Inicializar la API
new Flavor_Module_Config_API();

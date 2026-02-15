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
        $configs = array();

        foreach ($this->modules_config as $id => $config) {
            // Solo incluir módulos activos
            if ($this->is_module_active($id)) {
                $configs[$id] = $config;
            }
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data'    => $configs,
            'count'   => count($configs),
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

        if (!$this->is_module_active($module_id)) {
            return new WP_REST_Response(array(
                'success' => false,
                'error'   => 'Módulo no activo',
            ), 403);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data'    => $this->modules_config[$module_id],
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
